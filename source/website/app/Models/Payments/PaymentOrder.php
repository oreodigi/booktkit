<?php
namespace App\Models\Payments;
use Illuminate\Database\Eloquent\Model;
class PaymentOrder extends Model {
 protected $guarded=[];
 protected $casts=['pricing_snapshot'=>'array','customer_snapshot'=>'array','gateway_payload'=>'array','paid_at'=>'datetime','reconciled_at'=>'datetime'];
 public function ledger(){ return $this->hasMany(PaymentLedgerEntry::class); }
 public function transfers(){ return $this->hasMany(PaymentTransfer::class); }
 public function refunds(){ return $this->hasMany(PaymentRefund::class); }
 public function feeLines(){ return $this->hasMany(PaymentOrderFeeLine::class); }
}