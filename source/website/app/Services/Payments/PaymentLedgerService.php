<?php
namespace App\Services\Payments;
use App\Models\Payments\PaymentOrder;
use App\Models\Payments\PaymentLedgerEntry;
class PaymentLedgerService {
 public function recordPaid(PaymentOrder $o): void {
  $rows=[
   ['entry_type'=>'customer_payment','account'=>'gateway_clearing','amount'=>$o->customer_total],
   ['entry_type'=>'organizer_payable','account'=>'organizer','amount'=>-$o->organizer_amount],
   ['entry_type'=>'platform_fee','account'=>'booktkit','amount'=>-$o->platform_fee],
  ];
  foreach($rows as $r) PaymentLedgerEntry::firstOrCreate(['payment_order_id'=>$o->id,'entry_type'=>$r['entry_type']],$r+['currency'=>$o->currency,'reference'=>$o->gateway_payment_id]);
 }
}