<?php

namespace App\Services\Access;

use App\Models\Access\Credential;
use App\Models\Access\CredentialReplacement;
use App\Models\Access\TicketCredential;
use App\Models\Event\IssuedTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CredentialReplacementService
{
    public function replace(
        int $issuedTicketId,
        int $newCredentialId,
        string $reason,
        string $actorType,
        int $actorId,
        int $chargeAmount = 0,
        ?string $paymentReference = null
    ): CredentialReplacement {
        return DB::transaction(function () use ($issuedTicketId, $newCredentialId, $reason, $actorType, $actorId, $chargeAmount, $paymentReference) {
            $ticket = IssuedTicket::whereKey($issuedTicketId)->lockForUpdate()->firstOrFail();
            $current = TicketCredential::where('issued_ticket_id', $ticket->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (!$current) {
                throw ValidationException::withMessages(['ticket' => 'This ticket has no active credential to replace.']);
            }

            $old = Credential::whereKey($current->credential_id)->lockForUpdate()->firstOrFail();
            $new = Credential::whereKey($newCredentialId)->lockForUpdate()->firstOrFail();

            if ((int) $new->event_id !== (int) $ticket->event_id ||
                (int) $new->organizer_id !== (int) $ticket->organizer_id ||
                $new->status !== 'unassigned') {
                throw ValidationException::withMessages(['credential' => 'Replacement credential is not available for this event.']);
            }

            $current->update([
                'status' => 'replaced',
                'ended_at' => now(),
                'ended_reason' => $reason,
            ]);

            $old->update([
                'status' => $reason === 'lost' ? 'lost' : 'revoked',
                'revoked_at' => now(),
                'revocation_reason' => $reason,
            ]);

            TicketCredential::create([
                'issued_ticket_id' => $ticket->id,
                'credential_id' => $new->id,
                'status' => 'active',
                'assigned_by_type' => $actorType,
                'assigned_by_id' => $actorId,
                'assigned_at' => now(),
            ]);

            $new->update(['status' => 'active', 'activated_at' => now()]);

            return CredentialReplacement::create([
                'uuid' => (string) Str::uuid(),
                'issued_ticket_id' => $ticket->id,
                'old_credential_id' => $old->id,
                'new_credential_id' => $new->id,
                'reason' => $reason,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'charge_amount' => max(0, $chargeAmount),
                'charge_currency' => 'INR',
                'payment_reference' => $paymentReference,
            ]);
        }, 3);
    }
}
