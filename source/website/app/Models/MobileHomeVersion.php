<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MobileHomeVersion extends Model {
 protected $fillable=['campaign_id','version','snapshot','published_by','published_at'];
 protected $casts=['snapshot'=>'array','published_at'=>'datetime'];
}
