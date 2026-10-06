<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Model;

class EventAccessPolicy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'credential_types' => 'array',
        'collection_required' => 'boolean',
        'allow_ticket_qr_before_assignment' => 'boolean',
        'exit_scan_required' => 'boolean',
        'replacement_allowed' => 'boolean',
        'is_enabled' => 'boolean',
        'metadata' => 'array',
    ];
}
