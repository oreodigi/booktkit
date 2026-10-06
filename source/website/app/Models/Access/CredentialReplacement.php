<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Model;

class CredentialReplacement extends Model
{
    protected $guarded = [];
    protected $casts = ['metadata' => 'array'];
}
