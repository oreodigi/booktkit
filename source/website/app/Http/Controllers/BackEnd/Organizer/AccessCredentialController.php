<?php

namespace App\Http\Controllers\BackEnd\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Access\Credential;
use App\Models\Access\CredentialBatch;
use App\Models\Access\EventAccessPolicy;
use App\Models\Access\TicketCredential;
use App\Models\Event;
use App\Models\Event\IssuedTicket;
use App\Services\Access\CredentialAssignmentService;
use App\Services\Access\CredentialInventoryService;
use App\Services\Access\CredentialReplacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccessCredentialController extends Controller
{
    public function index(Request $request)
    {
        $organizerId = auth('organizer')->id();
        $events = Event::where('organizer_id', $organizerId)->with('information')->orderByDesc('id')->get();
        $event = $request->integer('event_id') ? $events->firstWhere('id', $request->integer('event_id')) : $events->first();
        $policy = $event ? EventAccessPolicy::where('event_id', $event->id)->where('organizer_id', $organizerId)->first() : null;
        $batches = $event ? CredentialBatch::where('event_id', $event->id)->where('organizer_id', $organizerId)->withCount('credentials')->latest()->get() : collect();
        $assignments = $event ? TicketCredential::whereHas('ticket', fn ($q) => $q->where('event_id', $event->id)->where('organizer_id', $organizerId))->with(['ticket.booking', 'credential'])->latest('assigned_at')->limit(50)->get() : collect();

        return view('organizer.access.index', compact('events', 'event', 'policy', 'batches', 'assignments'));
    }

    public function savePolicy(Request $request, $eventId)
    {
        $organizerId = auth('organizer')->id();
        Event::where('organizer_id', $organizerId)->findOrFail($eventId);
        $data = $request->validate([
            'credential_mode' => ['required', Rule::in(['ticket_only','physical_required','hybrid'])],
            'credential_types' => 'nullable|array',
            'credential_types.*' => [Rule::in(['qr_wristband','rfid_card','nfc_wristband','qr_badge','physical_id'])],
            'reentry_policy' => ['required', Rule::in(['none','limited','unlimited'])],
            'max_reentries' => 'nullable|integer|min:1|max:1000',
            'max_replacements' => 'nullable|integer|min:1|max:100',
        ]);
        if ($data['reentry_policy'] === 'limited' && empty($data['max_reentries'])) {
            return back()->withErrors(['max_reentries' => 'Set the maximum number of re-entries.']);
        }

        EventAccessPolicy::updateOrCreate(
            ['event_id' => $eventId],
            array_merge($data, [
                'organizer_id' => $organizerId,
                'collection_required' => $request->boolean('collection_required'),
                'allow_ticket_qr_before_assignment' => $request->boolean('allow_ticket_qr_before_assignment'),
                'exit_scan_required' => $request->boolean('exit_scan_required'),
                'replacement_allowed' => $request->boolean('replacement_allowed'),
                'identity_verification_mode' => 'ticket',
                'is_enabled' => $request->boolean('is_enabled'),
            ])
        );
        return back()->with('success', 'Access policy saved.');
    }

    public function createBatch(Request $request, CredentialInventoryService $inventory)
    {
        $organizerId = auth('organizer')->id();
        $data = $request->validate([
            'event_id' => 'required|integer',
            'credential_type' => ['required', Rule::in(['qr_wristband','rfid_card','nfc_wristband','qr_badge','physical_id'])],
            'batch_code' => 'required|string|max:80',
            'identifiers' => 'required|string|max:200000',
        ]);
        Event::where('organizer_id', $organizerId)->findOrFail($data['event_id']);
        $identifiers = preg_split('/[\r\n,]+/', $data['identifiers'], -1, PREG_SPLIT_NO_EMPTY);
        $inventory->createBatch($organizerId, (int) $data['event_id'], $data['credential_type'], $data['batch_code'], $identifiers);
        return back()->with('success', count($identifiers).' credentials imported.');
    }

    public function assign(Request $request, CredentialInventoryService $inventory, CredentialAssignmentService $assignment)
    {
        $organizerId = auth('organizer')->id();
        $data = $request->validate(['ticket_uuid' => 'required|uuid', 'credential_identifier' => 'required|string|max:255']);
        $ticket = IssuedTicket::where('organizer_id', $organizerId)->where('uuid', $data['ticket_uuid'])->firstOrFail();
        $credential = $inventory->resolve($data['credential_identifier']);
        abort_unless($credential && (int) $credential->organizer_id === $organizerId, 404);
        $assignment->assign($ticket->id, $credential->id, 'organizer', $organizerId);
        return back()->with('success', 'Credential assigned and activated.');
    }

    public function replace(Request $request, CredentialInventoryService $inventory, CredentialReplacementService $replacement)
    {
        $organizerId = auth('organizer')->id();
        $data = $request->validate([
            'ticket_uuid' => 'required|uuid',
            'credential_identifier' => 'required|string|max:255',
            'reason' => ['required', Rule::in(['lost','damaged','unreadable','rfid_malfunction','staff_replacement','other'])],
        ]);
        $ticket = IssuedTicket::where('organizer_id', $organizerId)->where('uuid', $data['ticket_uuid'])->firstOrFail();
        $policy = EventAccessPolicy::where('event_id', $ticket->event_id)->where('organizer_id', $organizerId)->first();
        abort_unless($policy && $policy->is_enabled && $policy->replacement_allowed, 403, 'Credential replacement is not enabled for this event.');
        if ($policy->max_replacements) {
            $count = \App\Models\Access\CredentialReplacement::where('issued_ticket_id', $ticket->id)->count();
            abort_if($count >= $policy->max_replacements, 422, 'Maximum credential replacements reached.');
        }
        $credential = $inventory->resolve($data['credential_identifier']);
        abort_unless($credential && (int) $credential->organizer_id === $organizerId, 404);
        $replacement->replace($ticket->id, $credential->id, $data['reason'], 'organizer', $organizerId);
        return back()->with('success', 'Old credential revoked and replacement activated.');
    }
}
