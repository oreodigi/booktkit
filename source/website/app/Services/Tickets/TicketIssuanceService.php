<?php

namespace App\Services\Tickets;

use App\Models\Event\Booking;
use App\Models\Event\IssuedTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TicketIssuanceService
{
    public function ensureForBooking(Booking $booking): array
    {
        if (!Schema::hasTable('issued_tickets') || !in_array($booking->paymentStatus, ['completed', 'free'], true)) {
            return [];
        }

        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $existing = IssuedTicket::where('booking_id', $booking->id)->orderBy('id')->get();
            if ($existing->isNotEmpty()) {
                return $this->present($existing);
            }

            $definitions = $this->definitions($booking);
            $issued = [];

            foreach ($definitions as $definition) {
                $uuid = (string) Str::uuid();
                $token = $this->tokenForUuid($uuid);
                $ticket = IssuedTicket::create([
                    'uuid' => $uuid,
                    'booking_id' => $booking->id,
                    'event_id' => $booking->event_id,
                    'organizer_id' => $booking->organizer_id,
                    'customer_id' => is_numeric($booking->customer_id) ? (int) $booking->customer_id : null,
                    'ticket_type_id' => $definition['ticket_type_id'],
                    'legacy_unique_id' => $definition['legacy_unique_id'],
                    'ticket_name' => $definition['ticket_name'],
                    'token_hash' => hash('sha256', $token),
                    'status' => 'active',
                    'issued_at' => now(),
                ]);
                $ticket->setAttribute('plain_token', $token);
                $issued[] = $ticket;
            }

            return $this->present(collect($issued));
        });
    }

    private function definitions(Booking $booking): array
    {
        $variations = json_decode((string) $booking->variation, true);
        $definitions = [];

        if (is_array($variations) && count($variations)) {
            foreach ($variations as $variation) {
                $qty = max(1, (int) ($variation['qty'] ?? 1));
                for ($i = 0; $i < $qty; $i++) {
                    $legacy = (string) ($variation['unique_id'] ?? '');
                    if ($qty > 1) $legacy .= '-' . ($i + 1);
                    $definitions[] = [
                        'ticket_type_id' => isset($variation['ticket_id']) ? (int) $variation['ticket_id'] : null,
                        'legacy_unique_id' => $legacy ?: null,
                        'ticket_name' => $variation['name'] ?? 'Ticket',
                    ];
                }
            }
        } else {
            for ($i = 1; $i <= max(1, (int) $booking->quantity); $i++) {
                $definitions[] = [
                    'ticket_type_id' => $booking->ticket_id ?: null,
                    'legacy_unique_id' => (string) $i,
                    'ticket_name' => 'Ticket',
                ];
            }
        }

        return $definitions;
    }

    public function tokenForUuid(string $uuid): string
    {
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $uuid, (string) config('app.key'), true)), '+/', '-_'), '=');
        return 'btk_' . $uuid . '.' . $signature;
    }

    private function present($tickets): array
    {
        return $tickets->map(function (IssuedTicket $ticket) {
            return [
                'id' => $ticket->id,
                'uuid' => $ticket->uuid,
                'ticket_name' => $ticket->ticket_name ?: 'Ticket',
                'status' => $ticket->status,
                'checked_in_at' => optional($ticket->checked_in_at)->toIso8601String(),
                'token' => $ticket->getAttribute('plain_token') ?: $this->tokenForUuid($ticket->uuid),
            ];
        })->all();
    }
}
