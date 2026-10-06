<?php
namespace App\Models\Payments;use Illuminate\Database\Eloquent\Model;
class EventAdditionalFee extends Model{protected $guarded=[];protected $casts=['is_mandatory'=>'boolean','is_taxable'=>'boolean','is_active'=>'boolean','metadata'=>'array'];}