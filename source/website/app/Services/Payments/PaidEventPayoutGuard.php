<?php
namespace App\Services\Payments;
use App\Models\Event;
use App\Models\Payments\OrganizerPaymentProfile;
class PaidEventPayoutGuard {
 public function eventHasPaidTickets(Event $event): bool {
  return $event->tickets()->get()->contains(function($t){
   if((float)($t->price ?? $t->f_price ?? 0)>0) return true;
   foreach((array)(json_decode($t->variations ?: '[]',true) ?: []) as $v) if((float)($v['price'] ?? 0)>0) return true;
   return false;
  });
 }
 public function canPublish(Event $event): bool {
  if(!$this->eventHasPaidTickets($event)) return true;
  $p=OrganizerPaymentProfile::where('organizer_id',$event->organizer_id)->first();
  return $p && $p->kyc_status==='activated';
 }
}