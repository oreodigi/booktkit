<?php
namespace App\Services\Tickets;

use App\Services\Access\AccessControlService;

class TicketAdmissionService
{
    public function __construct(private AccessControlService $access) {}

    /**
     * Thin adapter over the unified admission engine. Every scanner context (gate, supervisor
     * override, event selected on the device) must reach the engine unchanged.
     */
    public function admit(string $token, string $actorType, int $actorId, ?string $deviceName = null, ?string $ip = null, string $direction = 'entry', ?int $gateId = null, bool $override = false, ?string $overrideReason = null, ?int $eventId = null): array
    {
        return $this->access->scan($token, $actorType, $actorId, $deviceName, $ip, $direction, $gateId, $override, $overrideReason, $eventId);
    }
}
