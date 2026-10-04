<?php
namespace App\Services;
use App\Models\MobileHomeCampaign;
use App\Models\MobileHomeTemplate;
use App\Models\MobileHomeSection;
class MobileHomeResolver {
 public function active(){
  return cache()->remember('mobile_home_active_campaign',300,function(){
   $now=now();
   return MobileHomeCampaign::with(['template','sections'=>fn($q)=>$q->where('enabled',true)->orderBy('position')])
    ->where('status','published')->where(fn($q)=>$q->whereNull('starts_at')->orWhere('starts_at','<=',$now))
    ->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>=',$now))
    ->orderByDesc('priority')->orderByDesc('is_default')->first();
  });
 }
 public function fallback(){
  $template=MobileHomeTemplate::where('slug','booktkit-modern')->where('is_active',true)->first();
  if(!$template)return null;
  $campaign=new MobileHomeCampaign(['name'=>'BookTKIT Modern','status'=>'fallback','priority'=>0,'is_default'=>true]);
  $campaign->setRelation('template',$template);
  $sections=collect([
   ['type'=>'hero','title'=>'Discover Events','position'=>1],
   ['type'=>'quick_categories','title'=>'Browse Categories','position'=>2],
   ['type'=>'featured_events','title'=>'Featured Events','position'=>3],
   ['type'=>'explore_categories','title'=>'Explore Categories','position'=>4],
   ['type'=>'organizer_cta','title'=>'Host an Event','position'=>5],
   ['type'=>'how_it_works','title'=>'How It Works','position'=>6],
   ['type'=>'partners','title'=>'Partners','position'=>7],
  ])->map(fn($s)=>new MobileHomeSection($s+['enabled'=>true]));
  $campaign->setRelation('sections',$sections); return $campaign;
 }
}
