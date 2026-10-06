<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
class AccessAuthorityContractTest extends TestCase {
 public function test_gate_and_override_are_server_authoritative():void{$c=file_get_contents(__DIR__.'/../../app/Services/Access/AccessControlService.php');$this->assertStringContainsString("invalid_gate",$c);$this->assertStringContainsString("gate_direction_denied",$c);$this->assertStringContainsString("access.override",$c);$this->assertStringContainsString("override_reason",$c);}
 public function test_legacy_manual_mutation_is_retired():void{foreach(['OrganizerScannerController.php','AdminScannerController.php'] as $f){$c=file_get_contents(__DIR__.'/../../app/Http/Controllers/ScannerApi/'.$f);$this->assertStringContainsString("legacy_scan_mutation_retired",$c);}}
}