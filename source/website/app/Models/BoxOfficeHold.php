<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BoxOfficeHold extends Model{protected $fillable=['uuid','organizer_id','event_id','location_id','staff_id','items','customer','status','expires_at','resumed_at'];protected $casts=['items'=>'array','customer'=>'array','expires_at'=>'datetime','resumed_at'=>'datetime'];}
