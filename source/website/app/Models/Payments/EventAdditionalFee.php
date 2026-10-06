<?php
namespace App\Models\Payments;
use Illuminate\Database\Eloquent\Model;
class EventAdditionalFee extends Model {protected $guarded=[];protected $casts=['mandatory'=>'boolean','taxable'=>'boolean','enabled'=>'boolean','sales_channels'=>'array','metadata'=>'array'];}