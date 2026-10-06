<?php

namespace App\Http\Controllers\BackEnd\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Access\Credential;
use App\Models\Access\CredentialBatch;
use App\Models\Access\EventAccessPolicy;
use App\Models\Access\TicketCredential;
use App\Models\Event;
use App\Models\Event\IssuedTicket;
use App\Models\Event\Ticket;
use App\Services\Access\CredentialAssignmentService;
use App\Services\Access\CredentialInventoryService;
use App\Services\Access\CredentialReplacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AccessCredentialController extends Controller
{
    public function index(Request $request)
    {
        $organizerId = auth('organizer')->id();
        $events = Event::where('organizer_id', $organizerId)->with('information')->orderByDesc('id')->get();
        $event = $request->integer('event_id') ? $events->firstWhere('id', $request->integer('event_id')) : $events->first();
        $policy = $event ? EventAccessPolicy::where('event_id', $event->id)->where('organizer_id', $organizerId)->first() : null;
        $batches = $event ? CredentialBatch::where('event_id', $event->id)->where('organizer_id', $organizerId)->withCount('credentials')->latest()->get() : collect();
        $stats = $event ? [
            'inside' => IssuedTicket::where('event_id', $event->id)->where('organizer_id', $organizerId)->where('presence_state', 'inside')->count(),
            'outside' => IssuedTicket::where('event_id', $event->id)->where('organizer_id', $organizerId)->where('presence_state', 'outside')->count(),
            'entries' => IssuedTicket::where('event_id', $event->id)->where('organizer_id', $organizerId)->sum('entry_count'),
            'exits' => IssuedTicket::where('event_id', $event->id)->where('organizer_id', $organizerId)->sum('exit_count'),
            'credentials' => Credential::where('event_id', $event->id)->where('organizer_id', $organizerId)->where('status', 'active')->count(),
        ] : ['inside'=>0,'outside'=>0,'entries'=>0,'exits'=>0,'credentials'=>0];
        $zones = $event ? DB::table('event_access_zones')->where('event_id',$event->id)->where('organizer_id',$organizerId)->orderBy('name')->get() : collect();
        $gates = $event ? DB::table('event_gates')->where('event_id',$event->id)->where('organizer_id',$organizerId)->orderBy('name')->get() : collect();
        $gateStats = $event ? DB::table('event_gates as g')->leftJoin('access_scans as s','s.gate_id','=','g.id')->where('g.event_id',$event->id)->where('g.organizer_id',$organizerId)->groupBy('g.id','g.name','g.code','g.mode')->orderBy('g.name')->get(['g.id','g.name','g.code','g.mode',DB::raw("SUM(CASE WHEN s.result='admitted' THEN 1 ELSE 0 END) as entries"),DB::raw("SUM(CASE WHEN s.result='exited' THEN 1 ELSE 0 END) as exits"),DB::raw("SUM(CASE WHEN s.result='denied' THEN 1 ELSE 0 END) as denied")]) : collect();
        $scans = $event ? DB::table('access_scans')->where('event_id',$event->id)->latest('created_at')->limit(100)->get() : collect();
        $assignments = $event ? TicketCredential::whereHas('ticket', fn ($q) => $q->where('event_id', $event->id)->where('organizer_id', $organizerId))->with(['ticket.booking', 'credential'])->latest('assigned_at')->limit(50)->get() : collect();

        return view('organizer.access.index', compact('events', 'event', 'policy', 'batches', 'assignments', 'stats', 'zones', 'gates', 'scans', 'gateStats'));
    }

    public function savePolicy(Request $request, $eventId)
    {
        $organizerId = auth('organizer')->id();
        Event::where('organizer_id', $organizerId)->findOrFail($eventId);
        $data = $request->validate([
            'credential_mode' => ['required', Rule::in(['ticket_only','physical_required','hybrid'])],
            'credential_types' => 'nullable|array',
            'credential_types.*' => [Rule::in(['qr_wristband','rfid_wristband','rfid_card','nfc_wristband','qr_badge','physical_id'])],
            'reentry_policy' => ['required', Rule::in(['none','limited','unlimited'])],
            'max_reentries' => 'nullable|integer|min:1|max:1000',
            'max_replacements' => 'nullable|integer|min:1|max:100',
            'replacement_fee' => 'nullable|numeric|min:0|max:100000',
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
                'metadata' => array_merge((array)($policyMetadata = optional(EventAccessPolicy::where('event_id',$eventId)->first())->metadata), ['replacement_fee_paise'=>(int)round(((float)($data['replacement_fee']??0))*100)]),
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
        $data = $request->validate(['ticket_reference' => 'required|string|max:255', 'credential_identifier' => 'required|string|max:255']);
        $ticket = $this->resolveTicket($data['ticket_reference'], $organizerId);
        $credential = $inventory->resolve($data['credential_identifier']);
        abort_unless($credential && (int) $credential->organizer_id === $organizerId, 404);
        $assignment->assign($ticket->id, $credential->id, 'organizer', $organizerId);
        return back()->with('success', 'Credential assigned and activated.');
    }

    public function replace(Request $request, CredentialInventoryService $inventory, CredentialReplacementService $replacement)
    {
        $organizerId = auth('organizer')->id();
        $data = $request->validate([
            'ticket_reference' => 'required|string|max:255',
            'credential_identifier' => 'required|string|max:255',
            'reason' => ['required', Rule::in(['lost','damaged','unreadable','rfid_malfunction','staff_replacement','other'])],
            'payment_reference' => 'nullable|string|max:190',
        ]);
        $ticket = $this->resolveTicket($data['ticket_reference'], $organizerId);
        $policy = EventAccessPolicy::where('event_id', $ticket->event_id)->where('organizer_id', $organizerId)->first();
        $ticketType = $ticket->ticket_type_id ? Ticket::find($ticket->ticket_type_id) : null;
        $replacementAllowed = $ticketType && $ticketType->admission_pass_type
            ? (bool) $ticketType->replacement_allowed
            : (bool) ($policy && $policy->is_enabled && $policy->replacement_allowed);
        $maxReplacements = $ticketType && $ticketType->admission_pass_type ? $ticketType->max_replacements : ($policy?->max_replacements);
        abort_unless($replacementAllowed, 403, 'Credential replacement is not enabled for this ticket.');
        if ($maxReplacements) {
            $count = \App\Models\Access\CredentialReplacement::where('issued_ticket_id', $ticket->id)->count();
            abort_if($count >= $maxReplacements, 422, 'Maximum credential replacements reached.');
        }
        $credential = $inventory->resolve($data['credential_identifier']);
        abort_unless($credential && (int) $credential->organizer_id === $organizerId, 404);
        $fee = $ticketType && $ticketType->admission_pass_type
            ? (int) $ticketType->replacement_fee_paise
            : (int) data_get($policy?->metadata, 'replacement_fee_paise', 0);
        if($fee>0 && empty($data['payment_reference'])) return back()->withErrors(['payment_reference'=>'Replacement fee payment must be recorded before activating the new credential.']);
        $replacement->replace($ticket->id,$credential->id,$data['reason'],'organizer',$organizerId,$fee,$data['payment_reference']??null);
        return back()->with('success', 'Old credential revoked and replacement activated.');
    }
    public function createZone(Request $request)
    {
        $organizerId = auth('organizer')->id();
        $data = $request->validate(['event_id'=>'required|integer','name'=>'required|string|max:100','code'=>'required|string|max:60']);
        Event::where('organizer_id',$organizerId)->findOrFail($data['event_id']);
        DB::table('event_access_zones')->insert(['event_id'=>$data['event_id'],'organizer_id'=>$organizerId,'name'=>$data['name'],'code'=>strtolower($data['code']),'active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Access zone created.');
    }

    public function createGate(Request $request)
    {
        $organizerId = auth('organizer')->id();
        $data = $request->validate(['event_id'=>'required|integer','name'=>'required|string|max:100','code'=>'required|string|max:60','mode'=>['required',Rule::in(['entry','exit','entry_exit'])],'zone_id'=>'nullable|integer']);
        Event::where('organizer_id',$organizerId)->findOrFail($data['event_id']);
        if (!empty($data['zone_id'])) abort_unless(DB::table('event_access_zones')->where('id',$data['zone_id'])->where('event_id',$data['event_id'])->where('organizer_id',$organizerId)->exists(),422);
        DB::table('event_gates')->insert(['event_id'=>$data['event_id'],'organizer_id'=>$organizerId,'zone_id'=>$data['zone_id']??null,'name'=>$data['name'],'code'=>strtolower($data['code']),'mode'=>$data['mode'],'active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Gate created.');
    }

    private function resolveTicket(string $reference, int $organizerId): IssuedTicket
    {
        $query = IssuedTicket::where('organizer_id', $organizerId);
        if (str_starts_with($reference, 'btk_')) {
            return $query->where('token_hash', hash('sha256', $reference))->firstOrFail();
        }
        return $query->where('uuid', $reference)->firstOrFail();
    }
}
