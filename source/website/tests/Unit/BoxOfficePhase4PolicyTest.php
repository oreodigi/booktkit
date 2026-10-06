<?php
namespace Tests\Unit;
use App\Models\OrganizerStaff;use Tests\TestCase;
class BoxOfficePhase4PolicyTest extends TestCase{
 public function test_sales_agent_can_sell_and_reprint_but_not_approve_voids():void{$s=new OrganizerStaff(['role'=>'sales_agent']);$this->assertTrue($s->hasPermission('box_office.sell'));$this->assertTrue($s->hasPermission('box_office.reprint'));$this->assertFalse($s->hasPermission('box_office.void_approve'));}
 public function test_ticket_checker_cannot_use_pos():void{$s=new OrganizerStaff(['role'=>'ticket_checker']);$this->assertFalse($s->hasPermission('box_office.sell'));}
}