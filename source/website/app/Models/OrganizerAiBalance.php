<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizerAiBalance extends Model
{
    use HasFactory;
    protected $fillable = [
        'organizer_id',
        'ai_engine',
        'ai_token_balance',
        'ai_image_balance',
        'total_ai_token_purchased',
        'total_ai_image_purchased',
        'total_ai_token_used',
        'total_ai_image_used',
    ];
}
