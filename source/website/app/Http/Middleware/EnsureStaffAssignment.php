<?php
namespace App\Http\Middleware;
use Closure;
class EnsureStaffAssignment {
 public function handle($request,Closure $next,$permission=null){
  $staff=auth('staff')->user() ?: auth('staff_sanctum')->user();
  if(!$staff||!$staff->active)abort(401);
  if($staff->must_change_password && !$request->routeIs('staff.password.*','staff.logout'))return redirect()->route('staff.password.edit');
  // Admin impersonation is for support only: no sales, shifts or other changes.
  if(session('staff_impersonated_by_admin') && !$request->isMethod('GET') && !$request->routeIs('staff.impersonation.end','staff.logout'))abort(403,'Read-only while an admin is viewing as this team member.');
  if($permission&&!$staff->hasPermission($permission))abort(403);
  $event=$request->route('event');$eventId=is_object($event)?$event->id:$event;
  $location=$request->route('location');$locationId=is_object($location)?$location->id:$location;
  if($eventId&&!($locationId!==null?$staff->assignedTo((int)$eventId,(int)$locationId):$staff->assignedToEvent((int)$eventId)))abort(403);
  return $next($request);
 }
}