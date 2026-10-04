<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MobileHomeTemplate extends Model {
 protected $fillable=['name','slug','template_key','thumbnail','is_active','design'];
 protected $casts=['is_active'=>'boolean','design'=>'array'];
 public function campaigns(){return $this->hasMany(MobileHomeCampaign::class,'template_id');}
}
