<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Event;
use App\Models\Event\Slot;
use App\Models\Event\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Every seat-map action names an event, ticket and/or slot in the route or request. All of them
 * must belong to the signed-in organizer, otherwise the request is rejected before it runs.
 */
trait GuardsSeatMapOwnership
{
    protected function guardSeatMapOwnership(string $guard): void
    {
        $this->middleware(function (Request $request, $next) use ($guard) {
            $organizerId = (int) optional(Auth::guard($guard)->user())->id;
            abort_if($organizerId < 1, 401);
            $route = $request->route();
            $eventIds = array_filter([$route?->parameter('event'), $request->input('event_id')], 'is_numeric');
            $ticketIds = array_filter([$route?->parameter('ticket'), $request->input('ticket_id')], 'is_numeric');
            $slotIds = array_filter([$request->input('slot_id')], 'is_numeric');

            foreach ($eventIds as $id) {
                abort_unless(Event::whereKey((int) $id)->where('organizer_id', $organizerId)->exists(), 404);
            }
            foreach ($ticketIds as $id) {
                abort_unless(Ticket::whereKey((int) $id)->whereHas('event', fn ($q) => $q->where('organizer_id', $organizerId))->exists(), 404);
            }
            foreach ($slotIds as $id) {
                abort_unless(Slot::whereKey((int) $id)->whereIn('event_id', Event::where('organizer_id', $organizerId)->select('id'))->exists(), 404);
            }
            if (!$eventIds && !$ticketIds && !$slotIds && $request->isMethod('post')) {
                abort(422, 'Missing seat map reference.');
            }
            return $next($request);
        });
    }
}
