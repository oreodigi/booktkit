<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
class ScannerGateOperationsContractTest extends TestCase {
 public function test_all_scanner_roles_have_gate_discovery():void{$r=file_get_contents(__DIR__.'/../../routes/scanner_api.php');foreach(['organizer.gates','admin.gates','staff.gates'] as $x)$this->assertStringContainsString($x,$r);}
 public function test_gate_metrics_use_access_ledger():void{$c=file_get_contents(__DIR__.'/../../app/Http/Controllers/BackEnd/Organizer/AccessCredentialController.php');$this->assertStringContainsString("Gate Throughput",file_get_contents(__DIR__.'/../../resources/views/organizer/access/index.blade.php'));$this->assertStringContainsString("access_scans as s",$c);}
}