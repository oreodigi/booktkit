<?php
namespace App\Models\Payments;
use Illuminate\Database\Eloquent\Model;
class PaymentWebhookEvent extends Model { protected $guarded=[]; protected $casts=['payload'=>'array','processed_at'=>'datetime']; }