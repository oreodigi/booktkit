<?php
namespace App\Services\Payments;
use App\Models\Payments\OrganizerPaymentProfile;
class PlatformFeeCalculator {
 public function calculate(int $ticketAmountPaise, ?OrganizerPaymentProfile $profile): array {
  $type=$profile->fee_type ?? 'percentage';
  $value=(float)($profile->fee_value ?? 0);
  $fixed=(float)($profile->fee_fixed ?? 0);
  $percentage=(int)round($ticketAmountPaise*$value/100);
  $fixedPaise=(int)round($fixed*100);
  $fee=$type==='fixed' ? $fixedPaise : ($type==='hybrid' ? $percentage+$fixedPaise : $percentage);
  $bearer=$profile->fee_bearer ?? 'included';
  return [
   'ticket_amount'=>$ticketAmountPaise,
   'platform_fee'=>$fee,
   'customer_total'=>$bearer==='additional' ? $ticketAmountPaise+$fee : $ticketAmountPaise,
   'organizer_amount'=>max(0,$ticketAmountPaise-($bearer==='included' ? $fee : 0)),
   'fee_bearer'=>$bearer
  ];
 }
}