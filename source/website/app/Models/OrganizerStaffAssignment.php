<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrganizerStaffAssignment extends Model {
 protected $fillable=['staff_id','event_id','box_office_location_id'];
 public function staff(){return $this->belongsTo(OrganizerStaff::class,'staff_id');}
 public function event(){return $this->belongsTo(Event::class);}
 public function location(){return $this->belongsTo(\App\Models\Event\BoxOfficeLocation::class,'box_office_location_id');}
}