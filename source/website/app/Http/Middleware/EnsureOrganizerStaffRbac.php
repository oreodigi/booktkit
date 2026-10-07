<?php
namespace App\Http\Middleware;

use App\Models\BoxOfficeSale;
use App\Models\Event\Booking;
use App\Models\Event\Ticket;
use Closure;
use Illuminate\Http\Request;

/**
 * Staff who signed in through the team login also hold an organizer session so they can use
 * organizer screens their permissions allow. This middleware is the boundary:
 *  - deny by default: unmapped organizer routes are organizer-only;
 *  - every mapped route needs the matching staff permission;
 *  - money, KYC, settlement, withdrawal and account routes are organizer-only;
 *  - event-scoped routes require an assignment to that event;
 *  - POS selling goes through the staff POS so sales carry the staff member and an open shift.
 */
class EnsureOrganizerStaffRbac
{
    public function handle(Request $request, Closure $next)
    {
        $staff = auth('staff')->user();
        if (!$staff) return $next($request);
        if (!$staff->active) abort(403, 'This team account is disabled.');
        if ($staff->must_change_password) return redirect()->route('staff.password.edit');

        $name = (string) optional($request->route())->getName();
        if ($name === 'organizer.boxoffice.index') return redirect()->route('staff.boxoffice.index');
        if ($name === 'organizer.boxoffice.store') abort(403, 'Team members sell from the Staff POS so each sale is linked to you and your shift.');

        $permission = $this->permissionFor($name, $request->method());
        $allowed = $permission === 'access.console'
            ? collect(['credentials.inventory', 'credentials.issue', 'credentials.replace', 'credentials.revoke', 'access.reports'])->contains(fn ($p) => $staff->hasPermission($p))
            : ($permission && $staff->hasPermission($permission));
        if (!$allowed) abort(403, 'You do not have permission to access this organizer area.');

        $eventId = $this->eventIdFor($request, $name);
        if ($eventId !== null && !$staff->assignedToEvent($eventId)) abort(403, 'You are not assigned to this event.');

        return $next($request);
    }

    private function permissionFor(string $name, string $method): ?string
    {
        $read = $method === 'GET' || $method === 'HEAD';
        if (str_starts_with($name, 'organizer.event_management.seat_mapping') || str_contains($name, 'ticket_setting')) return 'tickets.manage';
        if (str_starts_with($name, 'organizer.event_management.') || str_starts_with($name, 'organizer.add.event') || $name === 'choose-event-type'
            || in_array($name, ['organizer.event.update', 'organizer.event.images', 'organizer.event.imagesstore', 'organizer.event.imagermv', 'organizer.event.imgdbrmv'], true)) {
            return $read ? 'events.view' : 'events.manage';
        }
        if (str_starts_with($name, 'organizer.event.ticket') || str_starts_with($name, 'organizer.ticket_management.') || str_starts_with($name, 'organizer.delete.variation') || str_starts_with($name, 'organizer.event.passes')) return 'tickets.manage';
        if (str_starts_with($name, 'organizer.event.booking') || str_starts_with($name, 'organizer.event_booking.')) return $read ? 'bookings.view' : 'bookings.manage';
        if (str_starts_with($name, 'organizer.boxoffice.reports')) return 'reports.view';
        if (str_starts_with($name, 'organizer.boxoffice.shifts')) return 'shifts.verify';
        if (str_starts_with($name, 'organizer.boxoffice.void.approve')) return 'box_office.void_approve';
        if (str_starts_with($name, 'organizer.boxoffice.void.request')) return 'box_office.void_request';
        if (str_starts_with($name, 'organizer.boxoffice.reprint')) return 'box_office.reprint';
        if (str_starts_with($name, 'organizer.boxoffice.settings')) return null; // organizer-only
        if (str_starts_with($name, 'organizer.boxoffice.')) return 'box_office.sell';
        if ($name === 'organizer.access.assign') return 'credentials.issue';
        if ($name === 'organizer.access.replace') return 'credentials.replace';
        if ($name === 'organizer.access.revoke') return 'credentials.revoke';
        if (str_starts_with($name, 'organizer.access.')) return $read ? 'access.console' : 'credentials.inventory';
        if (str_starts_with($name, 'organizer.staff.')) return 'team.manage';
        if (str_starts_with($name, 'organizer.support_') || str_contains($name, 'support_ticket')) return 'support.manage';
        // Money: staff may look, never change settlement, KYC, withdrawals or ledgers.
        if (str_starts_with($name, 'organizer.payments.') || str_starts_with($name, 'organizer.payouts.') || str_starts_with($name, 'organizer.withdraw')
            || str_starts_with($name, 'organizer.witdraw') || str_starts_with($name, 'organizer.transcation') || str_starts_with($name, 'organizer.monthly_income')) {
            return $read ? 'payments.view' : null;
        }
        if (str_starts_with($name, 'organizer.ai_token_purchase')) return null; // buying credits is organizer-only
        if (str_starts_with($name, 'organizer.ai.') || str_starts_with($name, 'organizer.ai_')) return 'ai.use';
        return null;
    }

    /** The event a request acts on, when it can be determined, for assignment scoping. */
    private function eventIdFor(Request $request, string $name): ?int
    {
        $route = $request->route();
        foreach (['eventId', 'event'] as $param) {
            $value = $route ? $route->parameter($param) : null;
            if ($value !== null) return (int) (is_object($value) ? $value->id : $value);
        }
        $id = $route ? $route->parameter('id') : null;
        if ($id !== null) {
            if (in_array($name, ['organizer.event_management.edit_event', 'organizer.event_management.event.event_status', 'organizer.event_management.event.update_featured',
                'organizer.event_management.duplicate_event', 'organizer.event_management.delete_event', 'organizer.event_management.ticket_setting', 'organizer.event.images'], true)) {
                return (int) $id;
            }
            if (str_starts_with($name, 'organizer.event_booking.')) return ($e = Booking::whereKey($id)->value('event_id')) ? (int) $e : null;
            if (str_starts_with($name, 'organizer.boxoffice.') && str_contains((string) $request->path(), '/sales/')) return ($e = BoxOfficeSale::whereKey($id)->value('event_id')) ? (int) $e : null;
        }
        if ($request->filled('event_id') && is_numeric($request->input('event_id'))) return (int) $request->input('event_id');
        if ($request->filled('ticket_id') && is_numeric($request->input('ticket_id'))) return ($e = Ticket::whereKey($request->input('ticket_id'))->value('event_id')) ? (int) $e : null;
        return null;
    }
}
