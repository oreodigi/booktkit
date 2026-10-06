<?php

namespace App\Models\Access;

use App\Models\Event\IssuedTicket;
use Illuminate\Database\Eloquent\Model;

class TicketCredential extends Model
{
    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(IssuedTicket::class, 'issued_ticket_id');
    }

    public function credential()
    {
        return $this->belongsTo(Credential::class);
    }
}
