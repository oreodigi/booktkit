<?php

namespace App\Services\Events;

use App\Models\Event;
use App\Models\Event\EventContent;
use App\Models\Event\EventImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deletes draft/unused events only. An event with any booking, payment, Box Office sale or issued
 * ticket keeps its history; the organizer deactivates it instead.
 */
class EventDeletionService
{
    public const BLOCKED_MESSAGE = 'This event has bookings or payments, so it cannot be deleted. Set it to inactive to stop sales; its history is kept.';

    public function __construct(private CommercialRecordGuard $guard)
    {
    }

    public function canDelete(Event $event): bool
    {
        return !$this->guard->eventHasCommercialRecords((int) $event->id);
    }

    /** @return bool false when the event has commercial records and was not deleted */
    public function delete(Event $event): bool
    {
        if (!$this->canDelete($event)) return false;

        DB::transaction(function () use ($event) {
            foreach (EventImage::where('event_id', $event->id)->get() as $image) {
                @unlink(public_path('assets/admin/img/event-gallery/') . basename((string) $image->image));
                $image->delete();
            }
            EventContent::where('event_id', $event->id)->delete();
            foreach (['wishlists', 'event_dates', 'box_office_locations', 'event_access_policies', 'event_gates', 'event_access_zones'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'event_id')) DB::table($table)->where('event_id', $event->id)->delete();
            }
            foreach ($event->tickets()->get() as $ticket) $ticket->delete();
            @unlink(public_path('assets/admin/img/event/thumbnail/') . basename((string) $event->thumbnail));
            $event->delete();
        });
        return true;
    }
}
