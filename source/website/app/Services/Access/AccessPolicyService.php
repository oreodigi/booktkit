<?php

namespace App\Services\Access;

use App\Models\Access\EventAccessPolicy;
use App\Models\Event;

class AccessPolicyService
{
    public function forEvent(Event $event): array
    {
        $policy = EventAccessPolicy::where('event_id', $event->id)->first();

        if (!$policy || !$policy->is_enabled) {
            return [
                'enabled' => false,
                'credential_mode' => 'ticket_only',
                'credential_types' => [],
                'collection_required' => false,
                'allow_ticket_qr_before_assignment' => true,
                'reentry_policy' => $event->box_office_enabled ? ($event->reentry_policy ?: 'none') : 'none',
                'max_reentries' => $event->box_office_enabled ? $event->max_reentries : null,
                'exit_scan_required' => (bool) $event->box_office_enabled,
                'replacement_allowed' => false,
                'max_replacements' => null,
            ];
        }

        return [
            'enabled' => true,
            'credential_mode' => $policy->credential_mode,
            'credential_types' => $policy->credential_types ?: [],
            'collection_required' => $policy->collection_required,
            'allow_ticket_qr_before_assignment' => $policy->allow_ticket_qr_before_assignment,
            'reentry_policy' => $policy->reentry_policy,
            'max_reentries' => $policy->max_reentries,
            'exit_scan_required' => $policy->exit_scan_required,
            'replacement_allowed' => $policy->replacement_allowed,
            'max_replacements' => $policy->max_replacements,
        ];
    }
}
