<?php

namespace App\Services\BoxOffice;

use App\Models\BoxOfficeSale;
use App\Models\BoxOfficeVoidRequest;
use App\Services\Bookings\BookingStatusService;
use App\Services\Events\EventPassService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * POS void: request (with reason) then approve. Approval reverses the sale completely:
 * ticket, variation and pass stock return, issued tickets and their wristbands/cards are
 * revoked, the POS ledger is reversed. A staff member cannot approve a void they requested.
 */
class BoxOfficeVoidService
{
    public function __construct(
        private LockedTicketInventoryService $inventory,
        private BoxOfficeLedgerService $ledger,
        private BookingStatusService $bookings
    ) {
    }

    public function request(BoxOfficeSale $sale, string $reason, ?int $staffId, ?int $organizerId): BoxOfficeVoidRequest
    {
        if ($sale->status !== 'completed') throw ValidationException::withMessages(['sale' => 'Only completed sales can be voided.']);
        $request = BoxOfficeVoidRequest::firstOrCreate(['sale_id' => $sale->id], ['reason' => $reason, 'requested_by_staff_id' => $staffId, 'status' => 'pending']);
        $sale->logs()->create(['action' => 'void_requested', 'actor_staff_id' => $staffId, 'actor_organizer_id' => $staffId ? null : $organizerId, 'reason' => $reason]);
        return $request;
    }

    public function approve(int $saleId, int $organizerId, ?int $staffId = null): BoxOfficeSale
    {
        return DB::transaction(function () use ($saleId, $organizerId, $staffId) {
            $sale = BoxOfficeSale::where('organizer_id', $organizerId)->where('status', 'completed')->lockForUpdate()->findOrFail($saleId);
            $request = BoxOfficeVoidRequest::where('sale_id', $sale->id)->first();
            if ($staffId && $request && (int) $request->requested_by_staff_id === $staffId) {
                throw ValidationException::withMessages(['sale' => 'A different supervisor must approve a void you requested.']);
            }
            if ($sale->booking()->whereHas('issuedTickets', fn ($q) => $q->whereNotNull('checked_in_at'))->exists()) {
                throw ValidationException::withMessages(['sale' => 'Scanned tickets cannot be voided.']);
            }

            $items = (array) (($sale->pricing_snapshot ?? [])['items'] ?? []);
            $this->inventory->restore((int) $sale->event_id, $items);
            foreach ($items as $item) {
                if (!empty($item['pass_product_id'])) app(EventPassService::class)->release((int) $item['pass_product_id'], (int) $item['quantity']);
            }

            $sale->update(['status' => 'voided']);
            $sale->booking->update(['paymentStatus' => 'rejected']);
            $this->bookings->syncTickets($sale->booking->fresh(), 'pos_void');
            $this->ledger->reverse($sale);

            BoxOfficeVoidRequest::updateOrCreate(['sale_id' => $sale->id], [
                'status' => 'approved', 'approved_by_organizer_id' => $staffId ? null : $organizerId, 'approved_by_staff_id' => $staffId, 'resolved_at' => now(),
            ]);
            $sale->logs()->create(['action' => 'void_approved', 'actor_staff_id' => $staffId, 'actor_organizer_id' => $staffId ? null : $organizerId]);
            return $sale->fresh();
        });
    }
}
