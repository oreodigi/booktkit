<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class BoxOfficeVoidRequest extends Model{protected $fillable=['sale_id','requested_by_staff_id','approved_by_staff_id','approved_by_organizer_id','status','reason','resolved_at'];protected $casts=['resolved_at'=>'datetime'];}