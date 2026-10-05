<?php
namespace App\Http\Middleware;
use Closure;
class EnsureStaffAssignment {
 public function handle($request,Closure $next,$permission=null){
  $staff=auth('staff')->user() ?: auth('staff_sanctum')->user();
  if(!$staff||!$staff->active)abort(401);
  if($staff->must_change_password && !$request->routeIs('staff.password.*','staff.logout'))return redirect()->route('staff.password.edit');
  if($permission&&!$staff->hasPermission($permission))abort(403);
  $event=$request->route('event');$eventId=is_object($event)?$event->id:$event;
  if($eventId&&!$staff->assignedTo((int)$eventId,$request->route('location')))abort(403);
  return $next($request);
 }
}