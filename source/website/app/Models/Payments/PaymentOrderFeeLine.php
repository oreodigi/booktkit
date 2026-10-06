<?php
namespace App\Models\Payments;use Illuminate\Database\Eloquent\Model;
class PaymentOrderFeeLine extends Model{protected $guarded=[];protected $casts=['calculation_snapshot'=>'array'];public function order(){return $this->belongsTo(PaymentOrder::class,'payment_order_id');}}