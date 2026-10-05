<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BoxOfficeSetting extends Model{protected $fillable=['organizer_id','event_id','hold_minutes','allow_cash','allow_upi','allow_card','allow_other','require_customer_name','require_customer_phone','allow_aadhaar','allow_customer_photo','auto_email_ticket','receipt_width_mm'];protected $casts=['allow_cash'=>'boolean','allow_upi'=>'boolean','allow_card'=>'boolean','allow_other'=>'boolean','require_customer_name'=>'boolean','require_customer_phone'=>'boolean','allow_aadhaar'=>'boolean','allow_customer_photo'=>'boolean','auto_email_ticket'=>'boolean'];}
