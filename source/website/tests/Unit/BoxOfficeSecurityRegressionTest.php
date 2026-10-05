<?php
namespace Tests\Unit;
use App\Models\OrganizerStaff;
use Tests\TestCase;
class BoxOfficeSecurityRegressionTest extends TestCase {
 public function test_role_boundaries(): void {
  $checker=new OrganizerStaff(['role'=>'ticket_checker']);
  $sales=new OrganizerStaff(['role'=>'sales_agent']);
  $supervisor=new OrganizerStaff(['role'=>'box_office_supervisor']);
  $this->assertTrue($checker->hasPermission('tickets.scan'));
  $this->assertFalse($checker->hasPermission('box_office.sell'));
  $this->assertTrue($sales->hasPermission('box_office.sell'));
  $this->assertFalse($sales->hasPermission('box_office.void_approve'));
  $this->assertTrue($supervisor->hasPermission('box_office.void_approve'));
  $this->assertTrue($supervisor->hasPermission('shifts.verify'));
 }
}