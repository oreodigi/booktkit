<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AccessControlContractTest extends TestCase
{
    public function test_access_engine_is_credential_aware_and_transactional(): void
    {
        $code = file_get_contents(__DIR__.'/../../app/Services/Access/AccessControlService.php');
        $this->assertStringContainsString("lockForUpdate()", $code);
        $this->assertStringContainsString("identifier_hash", $code);
        $this->assertStringContainsString("event_access_policies", $code);
        $this->assertStringContainsString("access_scans", $code);
        $this->assertStringNotContainsString("box_office_enabled", $code);
    }

    public function test_legacy_admission_delegates_to_access_engine(): void
    {
        $code = file_get_contents(__DIR__.'/../../app/Services/Tickets/TicketAdmissionService.php');
        $this->assertStringContainsString("AccessControlService", $code);
        $this->assertStringContainsString("->scan(", $code);
    }

    public function test_scanner_contract_has_staff_and_direction(): void
    {
        $routes = file_get_contents(__DIR__.'/../../routes/scanner_api.php');
        $admin = file_get_contents(__DIR__.'/../../app/Http/Controllers/ScannerApi/AdminScannerController.php');
        $this->assertStringContainsString("prefix('/staff')", $routes);
        $this->assertStringContainsString("'direction'=>'nullable|in:entry,exit'", $admin);
    }
}
