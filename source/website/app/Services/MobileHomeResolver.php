<?php
namespace App\Services;
use App\Models\MobileHomeCampaign;
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
}
