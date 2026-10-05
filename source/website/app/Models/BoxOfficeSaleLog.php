<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class BoxOfficeSaleLog extends Model{protected $fillable=['sale_id','action','actor_staff_id','actor_organizer_id','reason','metadata'];protected $casts=['metadata'=>'array'];}