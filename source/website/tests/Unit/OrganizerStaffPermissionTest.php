<?php
namespace Tests\Unit;
use App\Models\OrganizerStaff;
use PHPUnit\Framework\TestCase;
class OrganizerStaffPermissionTest extends TestCase {
 public function test_fixed_roles_expose_only_configured_permissions():void{
  $sales=new OrganizerStaff(['role'=>'sales_agent']);
  $checker=new OrganizerStaff(['role'=>'ticket_checker']);
  $supervisor=new OrganizerStaff(['role'=>'box_office_supervisor']);
  $this->assertTrue($sales->hasPermission('box_office.sell'));
  $this->assertFalse($sales->hasPermission('tickets.scan'));
  $this->assertTrue($checker->hasPermission('tickets.scan'));
  $this->assertFalse($checker->hasPermission('box_office.sell'));
  $this->assertTrue($supervisor->hasPermission('box_office.void_approve'));
 }
}