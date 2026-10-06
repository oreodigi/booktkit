<?php

namespace App\Models\Access;

use App\Models\Event\IssuedTicket;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    protected $guarded = [];

    protected $hidden = ['identifier_hash'];

    protected $casts = [
        'activated_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function assignments()
    {
        return $this->hasMany(TicketCredential::class);
    }

    public function activeAssignment()
    {
        return $this->hasOne(TicketCredential::class)->where('status', 'active');
    }

    public function issuedTicket()
    {
        return $this->hasOneThrough(
            IssuedTicket::class,
            TicketCredential::class,
            'credential_id',
            'id',
            'id',
            'issued_ticket_id'
        )->where('ticket_credentials.status', 'active');
    }
}
