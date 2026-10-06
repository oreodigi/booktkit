<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureOrganizerStaffRbac {
 public function handle(Request $request,Closure $next){
  $staff=auth('staff')->user();
  if(!$staff)return $next($request);
  if(!$staff->active)abort(403,'This team account is disabled.');
  if($staff->must_change_password)return redirect()->route('staff.password.edit');
  $name=(string)optional($request->route())->getName();
  $permission=$this->permissionFor($name,$request->method());
  if(!$permission||!$staff->hasPermission($permission))abort(403,'You do not have permission to access this organizer area.');
  return $next($request);
 }
 private function permissionFor(string $name,string $method):?string{
  if(str_starts_with($name,'organizer.event_management.')||str_starts_with($name,'organizer.add.event')||$name==='choose-event-type')return $method==='GET'?'events.view':'events.manage';
  if(str_starts_with($name,'organizer.event.ticket')||str_starts_with($name,'organizer.ticket_management.')||str_starts_with($name,'organizer.delete.variation')||str_contains($name,'seat_mapping')||str_contains($name,'ticket_setting')||str_starts_with($name,'organizer.event.passes'))return 'tickets.manage';
  if(str_starts_with($name,'organizer.event.booking')||str_starts_with($name,'organizer.event_booking.'))return $method==='GET'?'bookings.view':'bookings.manage';
  if(str_starts_with($name,'organizer.boxoffice.reports'))return 'reports.view';
  if(str_starts_with($name,'organizer.boxoffice.shifts'))return 'shifts.verify';
  if(str_starts_with($name,'organizer.boxoffice.void.approve'))return 'box_office.void_approve';
  if(str_starts_with($name,'organizer.boxoffice.void.request'))return 'box_office.void_request';
  if(str_starts_with($name,'organizer.boxoffice.reprint'))return 'box_office.reprint';
  if(str_starts_with($name,'organizer.boxoffice.'))return 'box_office.sell';
  if(str_starts_with($name,'organizer.access.'))return 'credentials.inventory';
  if(str_starts_with($name,'organizer.staff.'))return 'team.manage';
  if(str_starts_with($name,'organizer.support_')||str_contains($name,'support_ticket'))return 'support.manage';
  if(str_starts_with($name,'organizer.payments.')||str_starts_with($name,'organizer.payouts.')||str_starts_with($name,'organizer.withdraw')||str_starts_with($name,'organizer.transcation')||str_starts_with($name,'organizer.monthly_income'))return 'payments.view';
  if(str_starts_with($name,'organizer.ai.')||str_starts_with($name,'organizer.ai_'))return 'ai.use';
  return null;
 }
}