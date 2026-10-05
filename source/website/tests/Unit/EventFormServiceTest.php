<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Event\EventDates;
use App\Models\Event\Ticket;
use App\Models\Organizer;
use App\Services\Events\EventActor;
use App\Services\Events\EventFormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventFormServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_cannot_update_or_duplicate_another_organizers_event(): void
    {
        $owner=Organizer::factory()->create(); $other=Organizer::factory()->create();
        $event=Event::factory()->create(['organizer_id'=>$owner->id,'event_type'=>'venue']);
        $actor=EventActor::organizer($other);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $actor->assertOwns($event);
    }

    public function test_duplicate_is_disabled_and_does_not_copy_bookings(): void
    {
        $organizer=Organizer::factory()->create();
        $event=Event::factory()->create(['organizer_id'=>$organizer->id,'status'=>1,'is_featured'=>'yes','event_type'=>'venue']);
        EventDates::create(['event_id'=>$event->id,'start_date'=>'2026-11-01','start_time'=>'10:00','end_date'=>'2026-11-01','end_time'=>'12:00']);
        Ticket::create(['event_id'=>$event->id,'pricing_type'=>'normal','price'=>100]);
        $copy=app(EventFormService::class)->duplicateEvent($event,EventActor::organizer($organizer));
        $this->assertSame(0,(int)$copy->status); $this->assertSame('no',$copy->is_featured);
        $this->assertCount(1,$copy->dates); $this->assertCount(1,$copy->tickets); $this->assertCount(0,$copy->booking);
    }

    public function test_multiple_date_update_preserves_supplied_date_id(): void
    {
        $organizer=Organizer::factory()->create();
        $event=Event::factory()->create(['organizer_id'=>$organizer->id,'event_type'=>'venue','date_type'=>'multiple']);
        $date=EventDates::create(['event_id'=>$event->id,'start_date'=>'2026-11-01','start_time'=>'10:00','end_date'=>'2026-11-01','end_time'=>'12:00']);
        $service=app(EventFormService::class);
        $method=new \ReflectionMethod($service,'syncDates'); $method->setAccessible(true);
        $method->invoke($service,$event,['date_type'=>'multiple','date_ids'=>[$date->id],'m_start_date'=>['2026-11-02'],'m_start_time'=>['11:00'],'m_end_date'=>['2026-11-02'],'m_end_time'=>['13:00']],true);
        $this->assertDatabaseHas('event_dates',['id'=>$date->id,'event_id'=>$event->id,'start_date'=>'2026-11-02']);
    }
}
