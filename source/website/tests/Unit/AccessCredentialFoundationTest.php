<?php

namespace Tests\Unit;

use App\Models\OrganizerStaff;
use App\Services\Access\CredentialInventoryService;
use Tests\TestCase;

class AccessCredentialFoundationTest extends TestCase
{
    public function test_credential_identifier_hash_is_deterministic_and_does_not_store_plain_identifier(): void
    {
        $service = new CredentialInventoryService();
        $raw = 'BTK-WRISTBAND-000001';

        $hash = $service->hashIdentifier($raw);

        $this->assertSame(hash('sha256', $raw), $hash);
        $this->assertNotSame($raw, $hash);
        $this->assertSame(64, strlen($hash));
    }

    public function test_gate_checker_cannot_replace_credentials(): void
    {
        $checker = new OrganizerStaff(['role' => 'ticket_checker']);

        $this->assertTrue($checker->hasPermission('access.scan_entry'));
        $this->assertTrue($checker->hasPermission('access.scan_exit'));
        $this->assertFalse($checker->hasPermission('credentials.replace'));
        $this->assertFalse($checker->hasPermission('access.override'));
    }

    public function test_credential_issuer_cannot_override_access(): void
    {
        $issuer = new OrganizerStaff(['role' => 'credential_issuer']);

        $this->assertTrue($issuer->hasPermission('credentials.issue'));
        $this->assertTrue($issuer->hasPermission('credentials.replace'));
        $this->assertFalse($issuer->hasPermission('access.override'));
        $this->assertFalse($issuer->hasPermission('box_office.sell'));
    }
}
