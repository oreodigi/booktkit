<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MobileHomeCampaign extends Model {
 protected $fillable=['template_id','name','status','priority','starts_at','ends_at','targeting','is_default'];
 protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime','targeting'=>'array','is_default'=>'boolean'];
 public function template(){return $this->belongsTo(MobileHomeTemplate::class,'template_id');}
 public function sections(){return $this->hasMany(MobileHomeSection::class,'campaign_id')->orderBy('position');}
 public function versions(){return $this->hasMany(MobileHomeVersion::class,'campaign_id');}
}
