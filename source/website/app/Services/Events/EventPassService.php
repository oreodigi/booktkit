<?php

namespace App\Services\Events;

use App\Models\Event\EventPassProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EventPassService
{
 public function quote(int $eventId,array $selection,int $quantity,string $channel='web'): array
 {
  if(!Schema::hasTable('event_pass_products')) throw ValidationException::withMessages(['pass'=>'Event passes are not enabled.']);
  $pass=EventPassProduct::where('event_id',$eventId)->where('active',1)->whereKey((int)($selection['pass_product_id']??0))->firstOrFail();
  $channels=$pass->sales_channels?:['web','mobile','pos','box_office'];
  if(!in_array($channel,$channels,true)) throw ValidationException::withMessages(['pass'=>'This pass is not sold through this channel.']);
  if($quantity<1||$quantity>(int)$pass->max_per_order) throw ValidationException::withMessages(['pass'=>'Invalid pass quantity.']);
  if($pass->inventory_type==='limited' && ((int)$pass->inventory_quantity-(int)$pass->sold_quantity)<$quantity) throw ValidationException::withMessages(['pass'=>'Requested pass quantity is no longer available.']);
  $eligible=$pass->dates()->pluck('event_dates.id')->map(fn($v)=>(int)$v)->all();
  $selected=array_values(array_unique(array_map('intval',(array)($selection['event_date_ids']??[]))));
  if($pass->selection_mode==='all_dates'||$pass->selection_mode==='fixed_dates') $selected=$eligible;
  if($pass->selection_mode==='choose_n' && count($selected)!==(int)$pass->choose_count) throw ValidationException::withMessages(['pass'=>'Select exactly '.$pass->choose_count.' event dates.']);
  if(array_diff($selected,$eligible)) throw ValidationException::withMessages(['pass'=>'One or more selected dates are not valid for this pass.']);
  if(!$selected) throw ValidationException::withMessages(['pass'=>'Select at least one event date.']);
  return ['pass_product_id'=>$pass->id,'pass_name'=>$pass->name,'pass_type'=>$pass->pass_type,'ticket_id'=>$pass->ticket_id,'quantity'=>$quantity,'unit_price'=>(int)$pass->price,'line_total'=>(int)$pass->price*$quantity,'event_date_ids'=>$selected,'admissions_per_holder'=>(int)$pass->admissions_per_holder];
 }

 public function consume(int $passProductId,int $quantity): void
 {
  if(!$passProductId) return;
  $pass=EventPassProduct::whereKey($passProductId)->lockForUpdate()->firstOrFail();
  if($pass->inventory_type==='limited' && ((int)$pass->inventory_quantity-(int)$pass->sold_quantity)<$quantity) throw ValidationException::withMessages(['pass'=>'Pass stock changed before completion.']);
  $pass->sold_quantity=(int)$pass->sold_quantity+$quantity;$pass->save();
 }
}
