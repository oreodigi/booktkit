<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
class AccessOperationsContractTest extends TestCase {
 public function test_dashboard_uses_authoritative_access_ledger():void{
  $c=file_get_contents(__DIR__.'/../../app/Http/Controllers/BackEnd/Organizer/AccessCredentialController.php');
  $v=file_get_contents(__DIR__.'/../../resources/views/organizer/access/index.blade.php');
  $this->assertStringContainsString("access_scans",$c);
  $this->assertStringContainsString("event_access_zones",$c);
  $this->assertStringContainsString("event_gates",$c);
  $this->assertStringContainsString("Live Access Activity",$v);
 }
 public function test_scanner_exposes_entry_exit_mode():void{
  $p=file_get_contents(__DIR__.'/../../../scanner-app/lib/scanner/scanner_provider.dart');
  $q=file_get_contents(__DIR__.'/../../../scanner-app/lib/scanner/qr_scanner_page.dart');
  $this->assertStringContainsString("direction: _direction",$p);
  $this->assertStringContainsString("ENTRY",$q);
  $this->assertStringContainsString("EXIT",$q);
 }
}