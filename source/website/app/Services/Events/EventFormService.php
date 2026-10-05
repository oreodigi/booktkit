<?php

namespace App\Services\Events;

use App\Models\Event;
use App\Models\Language;
use App\Models\Organizer;
use App\Models\Event\EventContent;
use App\Models\Event\EventDates;
use App\Models\Event\EventImage;
use App\Models\Event\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;

class EventFormService
{
    public function createEvent(array $data, EventActor $actor): Event
    {
        return DB::transaction(function () use ($data, $actor) {
            $data['organizer_id'] = $actor->isOrganizer() ? $actor->organizerId() : ($data['organizer_id'] ?? null);
            $data = $this->normalizeEventData($data);
            $event = Event::create($data);
            $this->syncDates($event, $data, false);
            $this->syncContents($event, $data);
            $this->syncOnlineTicket($event, $data);
            $this->attachGallery($event, $data['slider_images'] ?? []);
            return $event->fresh(['dates','galleries','tickets']);
        });
    }

    public function updateEvent(Event $event, array $data, EventActor $actor): Event
    {
        $actor->assertOwns($event);
        return DB::transaction(function () use ($event, $data, $actor) {
            if ($actor->isOrganizer()) $data['organizer_id'] = $actor->organizerId();
            $data = $this->normalizeEventData($data);
            $event->update($data);
            $this->syncDates($event, $data, true);
            $this->syncContents($event, $data);
            $this->syncOnlineTicket($event, $data);
            return $event->fresh(['dates','galleries','tickets']);
        });
    }

    public function duplicateEvent(Event $source, EventActor $actor): Event
    {
        $actor->assertOwns($source);
        $source->load(['dates','galleries','tickets']);
        $createdFiles = [];
        try {
            return DB::transaction(function () use ($source, &$createdFiles) {
                $copy = $source->replicate();
                $copy->status = 0;
                $copy->is_featured = 'no';
                foreach (['thumbnail'=>'assets/admin/img/event/thumbnail/','ticket_image'=>'assets/admin/img/event_ticket/','ticket_logo'=>'assets/admin/img/event_ticket_logo/','ticket_slot_image'=>'assets/admin/img/map-image/'] as $field=>$dir) {
                    $copy->{$field} = $this->duplicateAsset($source->{$field}, $dir, $createdFiles);
                }
                $copy->save();

                EventContent::where('event_id',$source->id)->get()->each(function ($content) use ($copy) {
                    $new=$content->replicate(); $new->event_id=$copy->id; $new->title=$content->title.' - Copy';
                    $new->slug=createSlug($new->title.'-'.$copy->id); $new->google_calendar_id=null; $new->save();
                });
                foreach ($source->dates as $date) { $new=$date->replicate(); $new->event_id=$copy->id; $new->save(); }
                foreach ($source->galleries as $gallery) {
                    $new=$gallery->replicate(); $new->event_id=$copy->id;
                    $new->image=$this->duplicateAsset($gallery->image,'assets/admin/img/event-gallery/',$createdFiles); $new->save();
                }
                foreach ($source->tickets as $ticket) {
                    $new=$ticket->replicate(); $new->event_id=$copy->id;
                    $new->normal_ticket_slot_enable=0; $new->normal_ticket_slot_unique_id=null;
                    $new->free_tickete_slot_enable=0; $new->free_tickete_slot_unique_id=null;
                    $new->variations=$this->stripSlotIdentifiers($new->variations);
                    $new->trans_vars=$this->stripSlotIdentifiers($new->trans_vars); $new->save();
                }
                return $copy->fresh(['dates','galleries','tickets']);
            });
        } catch (\Throwable $e) {
            foreach ($createdFiles as $path) @unlink($path);
            throw $e;
        }
    }

    public function formData(?Event $event, EventActor $actor): array
    {
        if ($event) { $actor->assertOwns($event); $event->load(['dates','galleries','tickets']); }
        return ['event'=>$event,'languages'=>Language::all(),'organizers'=>$actor->isAdmin()?Organizer::all():collect()];
    }

    private function normalizeEventData(array $data): array
    {
        if (($data['date_type'] ?? null)==='single') {
            $start=Carbon::parse($data['start_date'].' '.$data['start_time']);
            $end=Carbon::parse($data['end_date'].' '.$data['end_time']);
            $data['duration']=DurationCalulate($start,$end); $data['end_date_time']=$end;
        } else { $data['start_date']=$data['start_time']=$data['end_date']=$data['end_time']=null; }
        if (($data['event_type'] ?? null)==='online') { $data['latitude']=null; $data['longitude']=null; }
        return $data;
    }

    private function syncDates(Event $event, array $data, bool $updating): void
    {
        if (($data['date_type'] ?? null)==='single') { EventDates::where('event_id',$event->id)->delete(); return; }
        $seen=[]; $firstDuration=null; $lastEnd=null;
        foreach (($data['m_start_date'] ?? []) as $i=>$date) {
            $start=Carbon::parse($date.' '.$data['m_start_time'][$i]); $end=Carbon::parse($data['m_end_date'][$i].' '.$data['m_end_time'][$i]);
            $values=['event_id'=>$event->id,'start_date'=>$date,'start_time'=>$data['m_start_time'][$i],'end_date'=>$data['m_end_date'][$i],'end_time'=>$data['m_end_time'][$i],'duration'=>DurationCalulate($start,$end),'start_date_time'=>$start,'end_date_time'=>$end];
            $id=$data['date_ids'][$i]??null;
            $row=$updating&&$id ? EventDates::where('event_id',$event->id)->whereKey($id)->first() : null;
            if ($row) { $row->update($values); $seen[]=$row->id; } else { $row=EventDates::create($values); $seen[]=$row->id; }
            $firstDuration ??= $values['duration']; if (!$lastEnd || $end->gt($lastEnd)) $lastEnd=$end;
        }
        if ($updating) EventDates::where('event_id',$event->id)->whereNotIn('id',$seen)->delete();
        $event->update(['duration'=>$firstDuration,'end_date_time'=>$lastEnd]);
    }

    private function syncContents(Event $event, array $data): void
    {
        foreach (Language::all() as $language) {
            $content=EventContent::firstOrNew(['event_id'=>$event->id,'language_id'=>$language->id]);
            $content->event_category_id=$data[$language->code.'_category_id']??null;
            $content->title=$data[$language->code.'_title']??''; $content->slug=createSlug($content->title);
            $content->description=Purifier::clean($data[$language->code.'_description']??'','youtube');
            $content->refund_policy=$data[$language->code.'_refund_policy']??null;
            $content->meta_keywords=$data[$language->code.'_meta_keywords']??null; $content->meta_description=$data[$language->code.'_meta_description']??null;
            if (($data['event_type']??null)==='venue') {
                $content->address=$data[$language->code.'_address']??null; $content->country_id=$data[$language->code.'_country']??null;
                $content->state_id=$data[$language->code.'_state']??null; $content->city_id=$data[$language->code.'_city']??null; $content->zip_code=$data[$language->code.'_zip_code']??null;
            } else { $content->address=null; $content->country_id=null; $content->state_id=null; $content->city_id=null; $content->zip_code=null; }
            $content->save();
        }
    }

    private function syncOnlineTicket(Event $event, array $data): void
    {
        if (($data['event_type']??null)!=='online') return;
        $ticket=Ticket::firstOrNew(['event_id'=>$event->id]);
        foreach (['price','ticket_available_type','ticket_available','max_ticket_buy_type','max_buy_ticket','early_bird_discount_amount','early_bird_discount_date','early_bird_discount_time'] as $f) $ticket->{$f}=$data[$f]??null;
        $ticket->f_price=$data['price']??null; $ticket->pricing_type=$data['pricing_type']??'normal';
        $ticket->early_bird_discount=$data['early_bird_discount_type']??null; $ticket->early_bird_discount_type=$data['discount_type']??null; $ticket->save();
    }

    private function attachGallery(Event $event, array $ids): void { EventImage::whereIn('id',$ids)->whereNull('event_id')->update(['event_id'=>$event->id]); }
    private function duplicateAsset(?string $filename,string $dir,array &$created): ?string {
        if (!$filename) return $filename; $directory=public_path($dir); $source=$directory.$filename; if(!is_file($source)) return $filename;
        @mkdir($directory,0775,true); $ext=pathinfo($filename,PATHINFO_EXTENSION); $name=uniqid('event-copy-',true).($ext?'.'.$ext:''); $dest=$directory.$name;
        if(!@copy($source,$dest)) throw new \RuntimeException('Unable to duplicate event media asset.'); $created[]=$dest; return $name;
    }
    private function stripSlotIdentifiers($value) { if(empty($value)) return $value; $d=json_decode($value,true); if(!is_array($d)) return $value; array_walk_recursive($d,function(&$v,$k){if($k==='slot_unique_id')$v=null;}); return json_encode($d); }
}
