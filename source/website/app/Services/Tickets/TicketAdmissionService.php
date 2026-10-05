<?php

namespace App\Services\Tickets;

use App\Models\Event\IssuedTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TicketAdmissionService
{
    public function admit(string $token, string $actorType, int $actorId, ?string $deviceName = null, ?string $ip = null): array
    {
        if (!Schema::hasTable('issued_tickets') || !str_starts_with($token, 'btk_')) {
            return ['alert_type' => 'error', 'message' => 'Invalid ticket'];
        }

        return DB::transaction(function () use ($token, $actorType, $actorId, $deviceName, $ip) {
            $ticket = IssuedTicket::with('booking')
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (!$ticket || !$ticket->booking) return ['alert_type' => 'error', 'message' => 'Invalid ticket'];

            $booking = $ticket->booking;
            if ($actorType === 'organizer' && (int) $booking->organizer_id !== $actorId) {
                return ['alert_type' => 'error', 'message' => 'You do not have permission'];
            }
            if (!in_array($booking->paymentStatus, ['completed', 'free'], true)) {
                return ['alert_type' => 'error', 'message' => 'Ticket payment is not valid'];
            }
            if ($ticket->status !== 'active') {
                return ['alert_type' => 'error', 'message' => 'Ticket is ' . $ticket->status];
            }
            if ($ticket->checked_in_at) {
                $this->log($ticket, $actorType, $actorId, 'already_used', $deviceName, $ip);
                return [
                    'alert_type' => 'error',
                    'message' => 'Already Scanned',
                    'booking_id' => $booking->booking_id,
                    'ticket_uuid' => $ticket->uuid,
                    'checked_in_at' => $ticket->checked_in_at->toIso8601String(),
                ];
            }

            $ticket->checked_in_at = now();
            $ticket->checked_in_by_type = $actorType;
            $ticket->checked_in_by_id = $actorId;
            $ticket->save();

            $legacyScanned = json_decode((string) $booking->scanned_tickets, true) ?: [];
            if ($ticket->legacy_unique_id !== null && !in_array((string) $ticket->legacy_unique_id, array_map('strval', $legacyScanned), true)) {
                $legacyScanned[] = $ticket->legacy_unique_id;
                $booking->scanned_tickets = json_encode(array_values($legacyScanned));
                $booking->save();
            }

            $this->log($ticket, $actorType, $actorId, 'admitted', $deviceName, $ip);

            return [
                'alert_type' => 'success',
                'message' => 'Verified',
                'booking_id' => $booking->booking_id,
                'ticket_uuid' => $ticket->uuid,
                'event_id' => $ticket->event_id,
                'ticket_name' => $ticket->ticket_name,
                'checked_in_at' => $ticket->checked_in_at->toIso8601String(),
            ];
        }, 3);
    }

    private function log(IssuedTicket $ticket, string $actorType, int $actorId, string $result, ?string $deviceName, ?string $ip): void
    {
        if (!Schema::hasTable('ticket_admission_logs')) return;
        DB::table('ticket_admission_logs')->insert([
            'issued_ticket_id' => $ticket->id,
            'booking_id' => $ticket->booking_id,
            'event_id' => $ticket->event_id,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'result' => $result,
            'device_name' => $deviceName,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
