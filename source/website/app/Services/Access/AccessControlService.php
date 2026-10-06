<?php

namespace App\Services\Access;

use App\Models\Access\Credential;
use App\Models\Access\EventAccessPolicy;
use App\Models\Access\TicketCredential;
use App\Models\Event\IssuedTicket;
use App\Models\OrganizerStaff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccessControlService
{
    public function scan(string $token, string $actorType, int $actorId, ?string $deviceName = null, ?string $ip = null, string $direction = 'entry'): array
    {
        if (!in_array($direction, ['entry', 'exit'], true)) return $this->deny('Invalid admission direction', 'invalid_direction');
        if (!Schema::hasTable('issued_tickets')) return $this->deny('Invalid ticket', 'invalid_token');

        return DB::transaction(function () use ($token, $actorType, $actorId, $deviceName, $ip, $direction) {
            [$ticket, $credential, $source] = $this->resolve($token);
            if (!$ticket || !$ticket->booking) return $this->deny('Invalid ticket or credential', 'invalid_token');

            $booking = $ticket->booking;
            if (!$this->actorCanScan($ticket, $booking->organizer_id, $actorType, $actorId, $direction)) {
                return $this->deny('You do not have permission', 'forbidden', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            }
            if (!in_array($booking->paymentStatus, ['completed', 'free'], true)) return $this->deny('Ticket payment is not valid', 'invalid_payment', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            if ($ticket->status !== 'active') return $this->deny('Ticket is '.$ticket->status, 'ticket_'.$ticket->status, $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);

            $policy = Schema::hasTable('event_access_policies') ? EventAccessPolicy::where('event_id', $ticket->event_id)->first() : null;
            $accessEnabled = $policy && $policy->is_enabled;

            if ($credential && !in_array($credential->status, ['assigned', 'active'], true)) {
                return $this->deny('Credential is '.$credential->status, 'credential_'.$credential->status, $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            }

            if ($accessEnabled && $policy->credential_mode === 'physical_required' && !$credential) {
                $activeCredential = TicketCredential::where('issued_ticket_id', $ticket->id)->where('status', 'active')->exists();
                if ($activeCredential || !$policy->allow_ticket_qr_before_assignment) {
                    return $this->deny('Physical credential required', 'credential_required', $ticket, null, $direction, $actorType, $actorId, $deviceName, $ip);
                }
            }

            $reentryPolicy = $accessEnabled ? $policy->reentry_policy : 'none';
            $maxReentries = $accessEnabled ? (int) $policy->max_reentries : 0;

            if (!$accessEnabled) {
                if ($direction === 'exit') return $this->deny('Exit scanning is not enabled for this event', 'exit_not_enabled', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
                if ($ticket->checked_in_at) return $this->deny('Already Scanned', 'already_used', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            }

            if ($direction === 'entry') {
                if ($accessEnabled && $ticket->presence_state === 'inside') return $this->deny('Ticket holder is already inside', 'already_inside', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
                if ($accessEnabled && (int) $ticket->entry_count > 0) {
                    if ($reentryPolicy === 'none') return $this->deny('Re-entry is not allowed', 'reentry_not_allowed', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
                    if ($reentryPolicy === 'limited' && ((int) $ticket->entry_count - 1) >= $maxReentries) return $this->deny('Re-entry limit reached', 'reentry_limit', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
                }
                $ticket->entry_count = (int) $ticket->entry_count + 1;
                $ticket->presence_state = 'inside';
                if (!$ticket->checked_in_at) $ticket->checked_in_at = now();
                $result = 'admitted';
            } else {
                if (!$accessEnabled) return $this->deny('Exit scanning is not enabled for this event', 'exit_not_enabled', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
                if ($ticket->presence_state !== 'inside') return $this->deny('Ticket holder is already outside', 'already_outside', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
                $ticket->exit_count = (int) $ticket->exit_count + 1;
                $ticket->presence_state = 'outside';
                $result = 'exited';
            }

            $ticket->last_admission_at = now();
            $ticket->checked_in_by_type = $actorType;
            $ticket->checked_in_by_id = $actorId;
            $ticket->save();

            $this->log($ticket, $credential, $actorType, $actorId, $direction, $result, null, $deviceName, $ip);

            return [
                'alert_type' => 'success',
                'message' => $direction === 'entry' ? 'Verified entry' : 'Verified exit',
                'booking_id' => $booking->booking_id,
                'ticket_uuid' => $ticket->uuid,
                'event_id' => $ticket->event_id,
                'ticket_name' => $ticket->ticket_name,
                'direction' => $direction,
                'presence_state' => $ticket->presence_state,
                'entry_count' => $ticket->entry_count,
                'exit_count' => $ticket->exit_count,
                'credential_type' => $credential?->type,
                'scan_source' => $source,
            ];
        }, 3);
    }

    private function resolve(string $token): array
    {
        $ticket = null; $credential = null; $source = 'ticket';
        if (str_starts_with($token, 'btk_')) {
            $ticket = IssuedTicket::with('booking')->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
        } elseif (Schema::hasTable('credentials')) {
            $credential = Credential::where('identifier_hash', hash('sha256', $token))->lockForUpdate()->first();
            if ($credential) {
                $assignment = TicketCredential::where('credential_id', $credential->id)->where('status', 'active')->lockForUpdate()->first();
                if ($assignment) $ticket = IssuedTicket::with('booking')->whereKey($assignment->issued_ticket_id)->lockForUpdate()->first();
                $source = 'credential';
            }
        }
        return [$ticket, $credential, $source];
    }

    private function actorCanScan(IssuedTicket $ticket, int $organizerId, string $actorType, int $actorId, string $direction): bool
    {
        if ($actorType === 'admin') return true;
        if ($actorType === 'organizer') return $organizerId === $actorId;
        if ($actorType !== 'staff') return false;
        $staff = OrganizerStaff::find($actorId);
        if (!$staff || !$staff->active || (int) $staff->organizer_id !== $organizerId || !$staff->assignedToEvent((int) $ticket->event_id)) return false;
        $permission = $direction === 'exit' ? 'access.scan_exit' : 'access.scan_entry';
        return $staff->hasPermission($permission) || $staff->hasPermission('tickets.scan');
    }

    private function deny(string $message, string $reason, ?IssuedTicket $ticket = null, ?Credential $credential = null, ?string $direction = null, ?string $actorType = null, ?int $actorId = null, ?string $deviceName = null, ?string $ip = null): array
    {
        if ($ticket && $actorType && $actorId) $this->log($ticket, $credential, $actorType, $actorId, $direction ?: 'entry', 'denied', $reason, $deviceName, $ip);
        return ['alert_type' => 'error', 'message' => $message, 'reason_code' => $reason];
    }

    private function log(IssuedTicket $ticket, ?Credential $credential, string $actorType, int $actorId, string $direction, string $result, ?string $reason, ?string $deviceName, ?string $ip): void
    {
        if (Schema::hasTable('access_scans')) DB::table('access_scans')->insert([
            'event_id' => $ticket->event_id, 'issued_ticket_id' => $ticket->id, 'credential_id' => $credential?->id,
            'actor_type' => $actorType, 'actor_id' => $actorId, 'action' => $direction, 'result' => $result,
            'reason_code' => $reason, 'device_name' => $deviceName, 'ip_address' => $ip,
            'state_snapshot' => json_encode(['presence_state'=>$ticket->presence_state,'entry_count'=>$ticket->entry_count,'exit_count'=>$ticket->exit_count]),
            'created_at' => now(),
        ]);
        if (Schema::hasTable('ticket_admission_logs')) DB::table('ticket_admission_logs')->insert([
            'issued_ticket_id'=>$ticket->id,'booking_id'=>$ticket->booking_id,'event_id'=>$ticket->event_id,'actor_type'=>$actorType,'actor_id'=>$actorId,
            'result'=>$result,'direction'=>$direction,'device_name'=>$deviceName,'ip_address'=>$ip,'created_at'=>now()
        ]);
    }
}
