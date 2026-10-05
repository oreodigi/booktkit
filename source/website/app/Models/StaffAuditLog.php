<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StaffAuditLog extends Model{protected $fillable=['organizer_id','staff_id','admin_id','action','meta','ip'];protected $casts=['meta'=>'array'];}
