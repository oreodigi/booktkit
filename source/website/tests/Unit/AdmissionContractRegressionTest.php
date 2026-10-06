<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdmissionContractRegressionTest extends TestCase
{
    public function test_current_admission_service_retains_transactional_ticket_lock_and_secure_token_lookup(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Services/Tickets/TicketAdmissionService.php');

        $this->assertStringContainsString("hash('sha256',\$token)", $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString('DB::transaction', $source);
        $this->assertStringContainsString("['entry','exit']", str_replace(' ', '', $source));
    }

    public function test_current_pos_sale_retains_idempotency_inventory_and_ticket_issuance(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Services/BoxOffice/BoxOfficeSaleService.php');

        $this->assertStringContainsString("where('uuid',\$data['sale_uuid'])", $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString('inventory->reserve', $source);
        $this->assertStringContainsString('issuance->ensureForBooking', $source);
    }
}
