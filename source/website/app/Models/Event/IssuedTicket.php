<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;

class IssuedTicket extends Model
{
    protected $fillable = [
        'uuid', 'booking_id', 'event_id', 'organizer_id', 'customer_id',
        'ticket_type_id', 'legacy_unique_id', 'ticket_name', 'token_hash',
        'status', 'issued_at', 'checked_in_at', 'checked_in_by_type',
        'checked_in_by_id',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    protected $hidden = ['token_hash'];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
