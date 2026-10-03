<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizerTokenPurchase extends Model
{
  use HasFactory;

  protected $fillable = [
    'organizer_id',
    'ai_token_package_id',
    'invoice_no',
    'ai_engine',
    'ai_token_limit',
    'ai_image_limit',
    'status',
    'price',
    'image',
    'conversation_id',
    'payment_method',
    'payment_status',
  ];
}
