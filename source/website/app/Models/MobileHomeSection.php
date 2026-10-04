<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MobileHomeSection extends Model {
 protected $fillable=['campaign_id','type','title','enabled','position','settings'];
 protected $casts=['enabled'=>'boolean','settings'=>'array'];
 public function campaign(){return $this->belongsTo(MobileHomeCampaign::class,'campaign_id');}
}
