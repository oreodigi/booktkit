<?php
namespace App\Models;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
class OrganizerStaff extends Model implements AuthenticatableContract {
 use Authenticatable,HasApiTokens;
 protected $table='organizer_staff';
 protected $fillable=['organizer_id','name','username','email','phone','photo','notes','password','role','department','job_title','active','must_change_password','last_login_at'];
 protected $hidden=['password','remember_token'];
 protected $casts=['active'=>'boolean','must_change_password'=>'boolean','last_login_at'=>'datetime'];
 public function organizer(){return $this->belongsTo(Organizer::class);}
 public function sales(){return $this->hasMany(BoxOfficeSale::class,'staff_id');}
 public function shifts(){return $this->hasMany(BoxOfficeShift::class,'staff_id');}
 public function auditLogs(){return $this->hasMany(StaffAuditLog::class,'staff_id');}
 public function assignments(){return $this->hasMany(OrganizerStaffAssignment::class,'staff_id');}
 public function hasPermission(string $permission):bool{return in_array($permission,config('staff.roles.'.$this->role.'.permissions',[]),true);}
 public function assignedTo(int $eventId,?int $locationId=null):bool{return $this->assignments()->where('event_id',$eventId)->when($locationId!==null,fn($q)=>$q->where('box_office_location_id',$locationId),fn($q)=>$q->whereNull('box_office_location_id'))->exists();}
 public function assignedToEvent(int $eventId):bool{return $this->assignments()->where('event_id',$eventId)->exists();}
}