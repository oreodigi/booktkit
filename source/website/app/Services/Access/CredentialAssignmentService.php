<?php

namespace App\Services\Access;

use App\Models\Access\Credential;
use App\Models\Access\TicketCredential;
use App\Models\Event\IssuedTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CredentialAssignmentService
{
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
}
