<?php

namespace App\Services\Bookings;

use App\Models\Event\Booking;
use App\Services\Tickets\TicketDeliveryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Manual booking status changes (offline payment approval) and keeping issued tickets and
 * physical credentials in step with the booking's commercial state.
 */
class BookingStatusService
{
    /**
     * Only bookings awaiting manual confirmation may be changed by hand: offline-gateway bookings
     * and pending legacy-app bookings. Razorpay (Payments V2) bookings are refunded through
     * Payment Operations; POS sales are voided from the Box Office.
     */
    public function manualChangeBlockedReason(Booking $booking): ?string
    {
        if ($booking->booking_source === 'box_office') return __('Box Office sales are cancelled with a void from the Box Office screen.');
        if (Schema::hasTable('payment_orders') && DB::table('payment_orders')->where('booking_id', $booking->id)->exists()) {
            return __('This booking was paid online through Razorpay. Use a refund from Payment Operations instead.');
        }
        if ($booking->gatewayType === 'offline' || $booking->paymentStatus === 'pending') return null;
        return __('Only offline or pending bookings can have their payment status changed manually.');
    }

    public function audit(Booking $booking, string $from, string $to, string $actorType, ?int $actorId): void
    {
        Log::notice('Booking payment status changed manually', [
            'booking_id' => $booking->booking_id, 'id' => $booking->id, 'from' => $from, 'to' => $to,
            'actor_type' => $actorType, 'actor_id' => $actorId, 'ip' => request()->ip(),
        ]);
    }

    /** Issued tickets follow the booking: valid only while it is completed or free. */
    public function syncTickets(Booking $booking, string $reason = 'booking_cancelled'): void
    {
        if (!Schema::hasTable('issued_tickets')) return;
        if (in_array($booking->paymentStatus, ['completed', 'free'], true)) {
            DB::table('issued_tickets')->where('booking_id', $booking->id)->where('status', 'revoked')
                ->update(['status' => 'active', 'updated_at' => now()]);
            return;
        }
        $ticketIds = DB::table('issued_tickets')->where('booking_id', $booking->id)->pluck('id');
        if ($ticketIds->isEmpty()) return;
        DB::table('issued_tickets')->whereIn('id', $ticketIds)->where('status', 'active')->update(['status' => 'revoked', 'updated_at' => now()]);
        if (Schema::hasTable('ticket_credentials')) {
            $credentialIds = DB::table('ticket_credentials')->whereIn('issued_ticket_id', $ticketIds)->where('status', 'active')->pluck('credential_id');
            DB::table('ticket_credentials')->whereIn('issued_ticket_id', $ticketIds)->where('status', 'active')
                ->update(['status' => 'revoked', 'ended_at' => now(), 'ended_reason' => $reason, 'updated_at' => now()]);
            if ($credentialIds->isNotEmpty() && Schema::hasTable('credentials')) {
                DB::table('credentials')->whereIn('id', $credentialIds)->whereIn('status', ['assigned', 'active'])
                    ->update(['status' => 'revoked', 'revoked_at' => now(), 'revocation_reason' => $reason, 'updated_at' => now()]);
            }
        }
    }

    public function deliverTickets(Booking $booking): void
    {
        app(TicketDeliveryService::class)->deliver($booking->fresh());
    }
}
