<?php

namespace App\Services\Access;

use App\Models\Access\Credential;
use App\Models\Access\CredentialBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CredentialInventoryService
{
    public function createBatch(int $organizerId, int $eventId, string $type, string $batchCode, array $identifiers): CredentialBatch
    {
        $identifiers = array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), $identifiers))));
        if (!$identifiers) {
            throw ValidationException::withMessages(['identifiers' => 'At least one credential identifier is required.']);
        }

        return DB::transaction(function () use ($organizerId, $eventId, $type, $batchCode, $identifiers) {
            $batch = CredentialBatch::create([
                'uuid' => (string) Str::uuid(),
                'organizer_id' => $organizerId,
                'event_id' => $eventId,
                'credential_type' => $type,
                'batch_code' => $batchCode,
                'expected_quantity' => count($identifiers),
                'status' => 'active',
            ]);

            foreach ($identifiers as $identifier) {
                $hash = $this->hashIdentifier($identifier);
                if (Credential::where('identifier_hash', $hash)->exists()) {
                    throw ValidationException::withMessages(['identifiers' => 'A credential identifier already exists.']);
                }

                Credential::create([
                    'uuid' => (string) Str::uuid(),
                    'batch_id' => $batch->id,
                    'organizer_id' => $organizerId,
                    'event_id' => $eventId,
                    'type' => $type,
                    'identifier_hash' => $hash,
                    'display_code' => $this->displayCode($identifier),
                    'status' => 'unassigned',
                ]);
            }

            return $batch->fresh('credentials');
        }, 3);
    }

    public function resolve(string $identifier): ?Credential
    {
        return Credential::where('identifier_hash', $this->hashIdentifier($identifier))->first();
    }

    public function hashIdentifier(string $identifier): string
    {
        return hash('sha256', trim($identifier));
    }

    private function displayCode(string $identifier): string
    {
        $identifier = trim($identifier);
        return strlen($identifier) <= 8 ? $identifier : substr($identifier, -8);
    }
}
