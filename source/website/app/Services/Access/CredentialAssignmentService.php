<?php

namespace App\Services\Access;

use App\Models\Access\Credential;
use App\Models\Access\TicketCredential;
use App\Models\Event\IssuedTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CredentialAssignmentService
{
    /** One credential vocabulary for tickets, access policies, inventory batches and passes. */
    public const TYPES = ['qr_wristband', 'rfid_wristband', 'rfid_card', 'nfc_wristband', 'qr_badge', 'physical_id'];

    public function assign(int $issuedTicketId, int $credentialId, string $actorType, int $actorId): TicketCredential
    {
        return DB::transaction(function () use ($issuedTicketId, $credentialId, $actorType, $actorId) {
            $ticket = IssuedTicket::whereKey($issuedTicketId)->lockForUpdate()->firstOrFail();
            $credential = Credential::whereKey($credentialId)->lockForUpdate()->firstOrFail();

            if ((int) $ticket->event_id !== (int) $credential->event_id ||
                (int) $ticket->organizer_id !== (int) $credential->organizer_id) {
                throw ValidationException::withMessages(['credential' => 'Credential does not belong to this ticket event.']);
            }

            if ($ticket->status !== 'active') {
                throw ValidationException::withMessages(['ticket' => 'Only active issued tickets can receive a credential.']);
            }

            if ($credential->status !== 'unassigned') {
                throw ValidationException::withMessages(['credential' => 'Credential is not available for assignment.']);
            }

            $required = $ticket->ticket_type_id ? \App\Models\Event\Ticket::whereKey($ticket->ticket_type_id)->value('admission_pass_type') : null;
            if ($required && $required !== 'mobile_qr' && $credential->type !== $required) {
                throw ValidationException::withMessages(['credential' => 'This ticket needs a ' . str_replace('_', ' ', $required) . ', not a ' . str_replace('_', ' ', $credential->type) . '.']);
            }

            if (TicketCredential::where('issued_ticket_id', $ticket->id)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['ticket' => 'This ticket already has an active credential.']);
            }

            $assignment = TicketCredential::create([
                'issued_ticket_id' => $ticket->id,
                'credential_id' => $credential->id,
                'status' => 'active',
                'assigned_by_type' => $actorType,
                'assigned_by_id' => $actorId,
                'assigned_at' => now(),
            ]);

            $credential->update([
                'status' => 'active',
                'activated_at' => now(),
                'revoked_at' => null,
                'revocation_reason' => null,
            ]);

            return $assignment->fresh(['ticket', 'credential']);
        }, 3);
    }

    /** Revoke a credential (lost, stolen, misuse...). Its ticket stays valid for a replacement. */
    public function revoke(int $credentialId, string $reason, string $actorType, int $actorId): void
    {
        DB::transaction(function () use ($credentialId, $reason, $actorType, $actorId) {
            $credential = Credential::whereKey($credentialId)->lockForUpdate()->firstOrFail();
            if ($credential->status === 'revoked') return;
            TicketCredential::where('credential_id', $credential->id)->where('status', 'active')
                ->update(['status' => 'revoked', 'ended_at' => now(), 'ended_reason' => $reason]);
            $metadata = (array) ($credential->metadata ?? []);
            $metadata['revoked_by'] = ['type' => $actorType, 'id' => $actorId, 'at' => now()->toIso8601String()];
            $credential->update(['status' => 'revoked', 'revoked_at' => now(), 'revocation_reason' => $reason, 'metadata' => $metadata]);
        }, 3);
    }
}
