<?php
namespace App\Models\Payments;
use Illuminate\Database\Eloquent\Model;
class PaymentLedgerEntry extends Model { protected $guarded=[]; protected $casts=['metadata'=>'array']; }