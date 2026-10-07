<?php

namespace App\Services\Access;

use App\Models\Access\Credential;
use App\Models\Access\EventAccessPolicy;
use App\Models\Access\TicketCredential;
use App\Models\Event\IssuedTicket;
use App\Models\Event\Ticket;
use App\Models\OrganizerStaff;
use App\Models\Event\PassEntitlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccessControlService
{
    public function scan(string $token, string $actorType, int $actorId, ?string $deviceName = null, ?string $ip = null, string $direction = 'entry', ?int $gateId = null, bool $override = false, ?string $overrideReason = null, ?int $eventId = null): array
    {
        if ($override && (!$overrideReason || mb_strlen($overrideReason) < 5)) return $this->deny('Supervisor override requires a reason', 'override_reason_required');
        if (!in_array($direction, ['entry', 'exit'], true)) return $this->deny('Invalid admission direction', 'invalid_direction');
        if (!Schema::hasTable('issued_tickets')) return $this->deny('Invalid ticket', 'invalid_token');

        return DB::transaction(function () use ($token, $actorType, $actorId, $deviceName, $ip, $direction, $gateId, $override, $overrideReason, $eventId) {
            [$ticket, $credential, $source] = $this->resolve($token);
            if (!$ticket || !$ticket->booking) return $this->deny('Invalid ticket or credential', 'invalid_token');

            $booking = $ticket->booking;
            if ($eventId && (int) $ticket->event_id !== (int) $eventId) return $this->deny('This ticket is for a different event', 'wrong_event', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            $gate = $gateId ? DB::table('event_gates')->where('id',$gateId)->where('event_id',$ticket->event_id)->where('active',1)->first() : null;
            if ($gateId && !$gate) return $this->deny('Invalid gate for this event','invalid_gate',$ticket,$credential,$direction,$actorType,$actorId,$deviceName,$ip);
            if ($gate && $gate->mode !== 'entry_exit' && $gate->mode !== $direction) return $this->deny('This gate does not allow '.strtoupper($direction),'gate_direction_denied',$ticket,$credential,$direction,$actorType,$actorId,$deviceName,$ip);
            if ($override && !$this->actorCanOverride($booking->organizer_id,$actorType,$actorId)) return $this->deny('Supervisor override permission required','override_forbidden',$ticket,$credential,$direction,$actorType,$actorId,$deviceName,$ip);
            if (!$this->actorCanScan($ticket, $booking->organizer_id, $actorType, $actorId, $direction)) {
                return $this->deny('You do not have permission', 'forbidden', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            }
            if (!in_array($booking->paymentStatus, ['completed', 'free'], true)) return $this->deny('Ticket payment is not valid', 'invalid_payment', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            if ($ticket->status !== 'active') return $this->deny('Ticket is '.$ticket->status, 'ticket_'.$ticket->status, $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            $eventDateId=null;
            if (!$ticket->pass_product_id && !$override && ($window = $this->outsideEventWindow((int) $ticket->event_id))) {
                return $this->deny($window[0], $window[1], $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            }
            if ($ticket->pass_product_id && Schema::hasTable('pass_entitlements')) {
                $today=now()->toDateString();
                $entitlement=PassEntitlement::where('issued_ticket_id',$ticket->id)->where('status','active')->whereHas('eventDate',fn($q)=>$q->whereDate('start_date','<=',$today)->where(function($d)use($today){$d->whereNull('end_date')->orWhereDate('end_date','>=',$today);} ))->orderBy('event_date_id')->first();
                if(!$entitlement) return $this->deny('This pass is not valid for today','pass_date_invalid',$ticket,$credential,$direction,$actorType,$actorId,$deviceName,$ip);
                $eventDateId=(int)$entitlement->event_date_id;
            }

            $policy = Schema::hasTable('event_access_policies') ? EventAccessPolicy::where('event_id', $ticket->event_id)->first() : null;
            $ticketType = $ticket->ticket_type_id && Schema::hasColumn('tickets', 'admission_pass_type') ? Ticket::find($ticket->ticket_type_id) : null;
            $ticketConfigured = $ticketType && $ticketType->admission_pass_type;
            $accessEnabled = $ticketConfigured || ($policy && $policy->is_enabled);
            $physicalRequired = $ticketConfigured
                ? $ticketType->admission_pass_type !== 'mobile_qr'
                : ($policy && $policy->credential_mode === 'physical_required');
            $allowQrBeforeAssignment = $ticketConfigured
                ? (bool) $ticketType->allow_mobile_qr_before_assignment
                : (!$policy || (bool) $policy->allow_ticket_qr_before_assignment);

            if ($credential && !in_array($credential->status, ['assigned', 'active'], true)) {
                return $this->deny('Credential is '.$credential->status, 'credential_'.$credential->status, $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            }

            if ($accessEnabled && $physicalRequired && !$credential) {
                $activeCredential = TicketCredential::where('issued_ticket_id', $ticket->id)->where('status', 'active')->exists();
                if ($activeCredential || !$allowQrBeforeAssignment) {
                    return $this->deny('Physical credential required', 'credential_required', $ticket, null, $direction, $actorType, $actorId, $deviceName, $ip);
                }
            }

            $reentryPolicy = $ticketConfigured ? $ticketType->reentry_policy : ($accessEnabled && $policy ? $policy->reentry_policy : 'none');
            $maxReentries = $ticketConfigured ? (int) $ticketType->max_reentries : ($accessEnabled && $policy ? (int) $policy->max_reentries : 0);

            if (!$accessEnabled) {
                if ($direction === 'exit') return $this->deny('Exit scanning is not enabled for this event', 'exit_not_enabled', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
                if ($ticket->checked_in_at) return $this->deny('Already Scanned', 'already_used', $ticket, $credential, $direction, $actorType, $actorId, $deviceName, $ip);
            }

            $state=null;
            if($eventDateId && Schema::hasTable('ticket_admission_states')){
                $state=DB::table('ticket_admission_states')->where('issued_ticket_id',$ticket->id)->where('event_date_id',$eventDateId)->lockForUpdate()->first();
                if(!$state){DB::table('ticket_admission_states')->insert(['issued_ticket_id'=>$ticket->id,'event_date_id'=>$eventDateId,'presence_state'=>'outside','entry_count'=>0,'exit_count'=>0,'created_at'=>now(),'updated_at'=>now()]);$state=DB::table('ticket_admission_states')->where('issued_ticket_id',$ticket->id)->where('event_date_id',$eventDateId)->lockForUpdate()->first();}
            }
            $presence=$state? $state->presence_state : $ticket->presence_state;$entries=$state?(int)$state->entry_count:(int)$ticket->entry_count;$exits=$state?(int)$state->exit_count:(int)$ticket->exit_count;
            if ($direction === 'entry') {
                if (!$override && $accessEnabled && $presence === 'inside') return $this->deny('Ticket holder is already inside','already_inside',$ticket,$credential,$direction,$actorType,$actorId,$deviceName,$ip);
                if (!$override && $accessEnabled && $entries > 0) {if($reentryPolicy==='none')return $this->deny('Re-entry is not allowed','reentry_not_allowed',$ticket,$credential,$direction,$actorType,$actorId,$deviceName,$ip);if($reentryPolicy==='limited'&&($entries-1)>=$maxReentries)return $this->deny('Re-entry limit reached','reentry_limit',$ticket,$credential,$direction,$actorType,$actorId,$deviceName,$ip);}
                $entries++;$presence='inside';$result='admitted';
            } else {if(!$accessEnabled)return $this->deny('Exit scanning is not enabled for this event','exit_not_enabled',$ticket,$credential,$direction,$actorType,$actorId,$deviceName,$ip);if(!$override&&$presence!=='inside')return $this->deny('Ticket holder is already outside','already_outside',$ticket,$credential,$direction,$actorType,$actorId,$deviceName,$ip);$exits++;$presence='outside';$result='exited';}
            if($state)DB::table('ticket_admission_states')->where('id',$state->id)->update(['presence_state'=>$presence,'entry_count'=>$entries,'exit_count'=>$exits,'first_entry_at'=>$state->first_entry_at?:($direction==='entry'?now():null),'last_admission_at'=>now(),'updated_at'=>now()]);
            $ticket->entry_count=$entries;$ticket->exit_count=$exits;$ticket->presence_state=$presence;if(!$ticket->checked_in_at&&$direction==='entry')$ticket->checked_in_at=now();
            $ticket->last_admission_at = now();
            $ticket->checked_in_by_type = $actorType;
            $ticket->checked_in_by_id = $actorId;
            $ticket->save();

            $this->log($ticket, $credential, $actorType, $actorId, $direction, $result, null, $deviceName, $ip, $gateId, $override, $overrideReason, $eventDateId);

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
                'gate_id' => $gateId,
                'override' => $override,
            ];
        }, 3);
    }

    /**
     * Non-pass tickets are admissible from the event's first day until its last session ends
     * (plus a grace period). Returns [message, reason] when outside that window.
     */
    private function outsideEventWindow(int $eventId): ?array
    {
        $event = DB::table('events')->where('id', $eventId)->first(['date_type', 'start_date', 'end_date_time']);
        if (!$event) return null;
        $firstDay = $event->date_type === 'multiple' && Schema::hasTable('event_dates')
            ? DB::table('event_dates')->where('event_id', $eventId)->min('start_date')
            : $event->start_date;
        $today = now()->startOfDay();
        try {
            if ($firstDay && \Carbon\Carbon::parse($firstDay)->startOfDay()->gt($today)) return ['This ticket is not valid until the event date', 'wrong_date'];
            $grace = max(0, (int) config('booktkit.admission_grace_hours', 6));
            if ($event->end_date_time && \Carbon\Carbon::parse($event->end_date_time)->addHours($grace)->isPast()) return ['This event has ended', 'event_ended'];
        } catch (\Throwable $e) {
            return null; // Unparseable legacy dates never block admission.
        }
        return null;
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

    private function actorCanOverride(int $organizerId,string $actorType,int $actorId): bool
    {
        if ($actorType === 'admin') return true;
        if ($actorType === 'organizer') return $organizerId === $actorId;
        $staff = $actorType === 'staff' ? OrganizerStaff::find($actorId) : null;
        return $staff && $staff->active && (int)$staff->organizer_id === $organizerId && $staff->hasPermission('access.override');
    }

    private function deny(string $message, string $reason, ?IssuedTicket $ticket = null, ?Credential $credential = null, ?string $direction = null, ?string $actorType = null, ?int $actorId = null, ?string $deviceName = null, ?string $ip = null): array
    {
        if ($ticket && $actorType && $actorId) $this->log($ticket, $credential, $actorType, $actorId, $direction ?: 'entry', 'denied', $reason, $deviceName, $ip);
        return ['alert_type' => 'error', 'message' => $message, 'reason_code' => $reason];
    }

    private function log(IssuedTicket $ticket, ?Credential $credential, string $actorType, int $actorId, string $direction, string $result, ?string $reason, ?string $deviceName, ?string $ip, ?int $gateId = null, bool $override = false, ?string $overrideReason = null, ?int $eventDateId = null): void
    {
        if (Schema::hasTable('access_scans')) DB::table('access_scans')->insert([
            'event_id' => $ticket->event_id, 'event_date_id'=>$eventDateId, 'issued_ticket_id' => $ticket->id, 'credential_id' => $credential?->id,
            'actor_type' => $actorType, 'actor_id' => $actorId, 'action' => $direction, 'result' => $result, 'gate_id'=>$gateId,
            'is_override'=>$override, 'override_reason'=>$overrideReason,
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
