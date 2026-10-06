<?php
namespace Tests\Unit;
use App\Models\OrganizerStaff;
use Tests\TestCase;
class OrganizerStaffPermissionTest extends TestCase{
 public function test_roles_expose_default_permissions():void{
  $sales=new OrganizerStaff(['role'=>'sales_agent']);
  $checker=new OrganizerStaff(['role'=>'ticket_checker']);
  $supervisor=new OrganizerStaff(['role'=>'box_office_supervisor']);
  $this->assertTrue($sales->hasPermission('box_office.sell'));
  $this->assertFalse($sales->hasPermission('tickets.scan'));
  $this->assertTrue($checker->hasPermission('tickets.scan'));
  $this->assertFalse($checker->hasPermission('box_office.sell'));
  $this->assertTrue($supervisor->hasPermission('box_office.void_approve'));
 }
 public function test_custom_permissions_override_role_defaults():void{
  $staff=new OrganizerStaff(['role'=>'sales_agent','permissions'=>['bookings.view']]);
  $this->assertTrue($staff->hasPermission('bookings.view'));
  $this->assertFalse($staff->hasPermission('box_office.sell'));
 }
}