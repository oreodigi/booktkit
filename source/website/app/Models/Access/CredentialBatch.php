<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Model;

class CredentialBatch extends Model
{
    protected $guarded = [];
    protected $casts = ['metadata' => 'array'];

    public function credentials()
    {
        return $this->hasMany(Credential::class, 'batch_id');
    }
}
