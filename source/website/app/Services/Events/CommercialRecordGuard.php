<?php

namespace App\Services\Events;

use App\Models\Event;
use App\Models\Event\Booking;
use App\Models\Event\EventPassProduct;
use App\Models\Event\IssuedTicket;
use App\Models\Event\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ownership and "has sales" checks shared by organizer, admin and API controllers.
 * Commercial history (bookings, payments, issued tickets, credentials) is never hard-deleted.
 */
class CommercialRecordGuard
{
    /** Event owned by the signed-in organizer (web or API), or 404. */
    public function ownedEvent($eventId, string $guard = 'organizer'): Event
    {
        $organizerId = (int) optional(Auth::guard($guard)->user())->id;
        return Event::whereKey((int) $eventId)->where('organizer_id', $organizerId)->firstOrFail();
    }

    /** Ticket whose event is owned by the signed-in organizer, or 404. */
    public function ownedTicket($ticketId, string $guard = 'organizer'): Ticket
    {
        $organizerId = (int) optional(Auth::guard($guard)->user())->id;
        return Ticket::whereKey((int) $ticketId)
            ->whereHas('event', fn ($q) => $q->where('organizer_id', $organizerId))
            ->firstOrFail();
    }

    public function ticketHasSales(Ticket $ticket): bool
    {
        $id = (int) $ticket->id;
        if (Schema::hasTable('issued_tickets') && IssuedTicket::where('ticket_type_id', $id)->exists()) return true;
        if (Schema::hasTable('event_pass_products') && EventPassProduct::where('ticket_id', $id)->exists()) return true;
        return Booking::where('event_id', $ticket->event_id)->where(function ($q) use ($id) {
            $q->where('variation', 'like', '%"ticket_id":' . $id . ',%')
              ->orWhere('variation', 'like', '%"ticket_id":' . $id . '}%')
              ->orWhere('variation', 'like', '%"ticket_id":"' . $id . '"%');
        })->exists();
    }

    public function eventHasCommercialRecords(int $eventId): bool
    {
        if (Booking::where('event_id', $eventId)->exists()) return true;
        foreach (['payment_orders', 'box_office_sales', 'issued_tickets'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->where('event_id', $eventId)->exists()) return true;
        }
        return false;
    }

    public function bookingIsCommercial(Booking $booking): bool
    {
        return in_array($booking->paymentStatus, ['completed', 'free'], true)
            || (Schema::hasTable('issued_tickets') && IssuedTicket::where('booking_id', $booking->id)->exists())
            || (Schema::hasTable('payment_orders') && DB::table('payment_orders')->where('booking_id', $booking->id)->exists());
    }
}
