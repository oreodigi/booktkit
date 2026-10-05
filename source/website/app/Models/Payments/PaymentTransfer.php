<?php
namespace App\Models\Payments;
use Illuminate\Database\Eloquent\Model;
class PaymentTransfer extends Model {
 protected $guarded=[];
 protected $casts=['gateway_payload'=>'array','processed_at'=>'datetime','hold_release_at'=>'datetime','released_at'=>'datetime','on_hold'=>'boolean'];
 public function order(){return $this->belongsTo(PaymentOrder::class,'payment_order_id');}
}