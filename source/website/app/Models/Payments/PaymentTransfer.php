<?php
namespace App\Models\Payments;
use Illuminate\Database\Eloquent\Model;
class PaymentTransfer extends Model { protected $guarded=[]; protected $casts=['gateway_payload'=>'array','processed_at'=>'datetime']; }