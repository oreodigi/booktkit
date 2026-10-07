<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Tickets\TicketAdmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Legacy scanner endpoints (organizer/admin web PWA, organizer app, old admin API) are kept only as
 * adapters: every scan goes through the unified admission engine. They never write
 * bookings.scanned_tickets. Old-format "BOOKINGID__UNIQUEID" QR codes are refused because they are
 * guessable; attendees use the secure QR from their email or account.
 */
trait AdmitsThroughUnifiedEngine
{
    protected function admitLegacyScan(Request $request, string $actorType, int $actorId): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => 'required|string|max:255',
            'direction' => 'nullable|in:entry,exit',
            'gate_id' => 'nullable|integer',
            'event_id' => 'nullable|integer',
        ]);
        $token = trim($data['booking_id']);

        if (!str_starts_with($token, 'btk_') && str_contains($token, '__')) {
            return response()->json([
                'alert_type' => 'error',
                'message' => 'Old-format QR code. Please scan the secure QR from the latest ticket email or the customer account.',
                'reason_code' => 'legacy_qr',
                'booking_id' => $token,
            ]);
        }

        $result = app(TicketAdmissionService::class)->admit(
            $token, $actorType, $actorId,
            $request->header('X-Device-Name') ?: 'legacy-scanner',
            $request->ip(),
            $data['direction'] ?? 'entry',
            isset($data['gate_id']) ? (int) $data['gate_id'] : null,
            false, null,
            isset($data['event_id']) ? (int) $data['event_id'] : null
        );
        $result['booking_id'] = $result['booking_id'] ?? $token;
        return response()->json($result);
    }
}
