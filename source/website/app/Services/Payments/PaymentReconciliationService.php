<?php
namespace App\Services\Payments;
use App\Models\Payments\PaymentOrder;
class PaymentReconciliationService {
 public function reconcile(PaymentOrder $o): array {
  $ledgerCustomer=(int)$o->ledger()->where('entry_type','customer_payment')->sum('amount');
  $transfer=(int)$o->transfers()->whereIn('status',['processed','created','pending'])->sum('amount');
  $refund=(int)$o->refunds()->whereIn('status',['processed','refunded'])->sum('amount');
  $ok=$o->status==='paid' && $ledgerCustomer===(int)$o->customer_total && $refund<=(int)$o->customer_total;
  if($ok) $o->update(['reconciled_at'=>now()]);
  return ['ok'=>$ok,'customer_total'=>$o->customer_total,'ledger_customer'=>$ledgerCustomer,'transferred'=>$transfer,'refunded'=>$refund];
 }
}