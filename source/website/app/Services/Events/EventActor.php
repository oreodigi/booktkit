<?php

namespace App\Services\Events;

use App\Models\Organizer;

final class EventActor
{
    private string $type;
    private ?int $organizerId;

    private function __construct(string $type, ?int $organizerId = null)
    {
        $this->type = $type;
        $this->organizerId = $organizerId;
    }

    public static function admin(): self { return new self('admin'); }
    public static function organizer(Organizer $organizer): self { return new self('organizer', (int) $organizer->id); }
    public function isAdmin(): bool { return $this->type === 'admin'; }
    public function isOrganizer(): bool { return $this->type === 'organizer'; }
    public function organizerId(): ?int { return $this->organizerId; }

    public function assertOwns(\App\Models\Event $event): void
    {
        if ($this->isOrganizer() && (int) $event->organizer_id !== $this->organizerId) {
            abort(403);
        }
    }
}
