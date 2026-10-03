<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiTokenPackage extends Model
{
  use HasFactory;

  protected $fillable = [
    'title',
    'ai_engine',
    'ai_token_limit',
    'ai_image_limit',
    'status',
    'price',
  ];
}
