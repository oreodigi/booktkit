<?php
namespace App\Models\Payments;use Illuminate\Database\Eloquent\Model;
class PaymentFeeRule extends Model{protected $guarded=[];protected $casts=['is_active'=>'boolean'];}