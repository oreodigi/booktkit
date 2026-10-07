<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Event\IssuedTicket;
use Illuminate\Support\Collection;

/**
 * Scanner-app attendee statistics, computed from issued tickets (the admission authority)
 * instead of the retired bookings.scanned_tickets column. Response shape is unchanged.
 */
trait ScannerTicketStats
{
    protected function scannerTicketStats(Collection $bookings): array
    {
        $issued = IssuedTicket::whereIn('booking_id', $bookings->pluck('id'))->get()->groupBy('booking_id');
        $all = collect();
        foreach ($bookings as $booking) {
            $rows = $issued->get($booking->id, collect());
            $valid = in_array($booking->paymentStatus, ['completed', 'free'], true);
            if ($rows->isNotEmpty()) {
                foreach ($rows as $ticket) {
                    $all->push([
                        'booking_id' => $booking->booking_id,
                        'event_id' => $booking->event_id,
                        'event_name' => optional($booking->event)->title,
                        'ticket_name' => $ticket->ticket_name,
                        'ticket_id' => $ticket->legacy_unique_id ?: $ticket->uuid,
                        'customer_phone' => $booking->phone,
                        'payment_status' => $booking->paymentStatus,
                        'scan_status' => $ticket->checked_in_at ? 'scanned' : 'unscanned',
                        'presence_state' => $ticket->presence_state,
                    ]);
                }
                continue;
            }
            // Not issued yet (never viewed/delivered): every unit is unscanned.
            foreach (range(1, max(1, (int) $booking->quantity)) as $index) {
                $all->push([
                    'booking_id' => $booking->booking_id,
                    'event_id' => $booking->event_id,
                    'event_name' => optional($booking->event)->title,
                    'ticket_name' => null,
                    'ticket_id' => $index,
                    'customer_phone' => $booking->phone,
                    'payment_status' => $booking->paymentStatus,
                    'scan_status' => 'unscanned',
                    'presence_state' => 'outside',
                ]);
            }
        }
        $scanned = $all->where('scan_status', 'scanned')->values();
        return [
            'total_attendees_tickets' => $all->count(),
            'total_scanned_tickets' => $scanned->count(),
            'total_unscanned_tickets' => $all->count() - $scanned->count(),
            'scanned_tickets' => $scanned,
            'unscanned_tickets' => $all->where('scan_status', 'unscanned')->values(),
            'all_tickets' => $all->values(),
        ];
    }
}
