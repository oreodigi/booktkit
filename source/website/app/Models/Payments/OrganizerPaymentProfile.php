<?php
namespace App\Models\Payments;
use Illuminate\Database\Eloquent\Model;
class OrganizerPaymentProfile extends Model {
 protected $guarded=[];
 protected $casts=['gst_verified'=>'boolean','split_enabled'=>'boolean','metadata'=>'array'];
 public function organizer(){ return $this->belongsTo(\App\Models\Organizer::class); }
 public function canSplit(): bool {
  return $this->settlement_mode==='razorpay_split' && $this->split_enabled && $this->razorpay_status==='active' && !empty($this->razorpay_account_id);
 }
}