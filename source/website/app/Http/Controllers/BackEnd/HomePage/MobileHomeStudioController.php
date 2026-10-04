<?php
namespace App\Http\Controllers\BackEnd\HomePage;

use App\Http\Controllers\Controller;
use App\Models\MobileHomeCampaign;
use App\Models\MobileHomeSection;
use App\Models\MobileHomeTemplate;
use App\Models\MobileHomeVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MobileHomeStudioController extends Controller {
 public function index(){
   $templates=MobileHomeTemplate::orderBy('name')->get();
   $campaigns=MobileHomeCampaign::with('template')->orderByDesc('is_default')->orderByDesc('priority')->get();
   return view('backend.home-page.mobile-home.index',compact('templates','campaigns'));
 }
 public function storeTemplate(Request $r){
   $data=$r->validate(['name'=>'required|max:100','template_key'=>'required|in:modern,festival,nightlife,sports,minimal']);
   $data['slug']=\Str::slug($data['name']).'-'.substr(uniqid(),-5);
   MobileHomeTemplate::create($data); return back()->with('success','Mobile homepage template created.');
 }
 public function storeCampaign(Request $r){
   $data=$r->validate(['name'=>'required|max:120','template_id'=>'required|exists:mobile_home_templates,id','priority'=>'nullable|integer','starts_at'=>'nullable|date','ends_at'=>'nullable|date|after_or_equal:starts_at']);
   $data['status']='draft'; $campaign=MobileHomeCampaign::create($data);
   $defaults=[['hero','Discover Events',1],['quick_categories','Event Categories',2],['featured_events','Featured Events',3],['explore_categories','Explore Categories',4],['how_it_works','How It Works',5],['partners','Partners',6]];
   foreach($defaults as $d) MobileHomeSection::create(['campaign_id'=>$campaign->id,'type'=>$d[0],'title'=>$d[1],'position'=>$d[2],'enabled'=>true]);
   return redirect()->route('admin.mobile_home.edit',$campaign)->with('success','Campaign created as draft.');
 }
 public function edit(MobileHomeCampaign $campaign){$campaign->load(['template','sections']);return view('backend.home-page.mobile-home.edit',compact('campaign'));}
 public function update(Request $r,MobileHomeCampaign $campaign){
   $campaign->update($r->validate(['name'=>'required|max:120','priority'=>'required|integer','starts_at'=>'nullable|date','ends_at'=>'nullable|date|after_or_equal:starts_at']));
   foreach($r->input('sections',[]) as $id=>$row){$s=$campaign->sections()->findOrFail($id);$s->update(['title'=>$row['title']??$s->title,'enabled'=>isset($row['enabled']),'position'=>(int)($row['position']??$s->position),'settings'=>$row['settings']??$s->settings]);}
   return back()->with('success','Draft saved.');
 }
 public function publish(MobileHomeCampaign $campaign){
   DB::transaction(function()use($campaign){$campaign->load(['template','sections']);$v=(int)$campaign->versions()->max('version')+1;$snapshot=$campaign->toArray();MobileHomeVersion::create(['campaign_id'=>$campaign->id,'version'=>$v,'snapshot'=>$snapshot,'published_by'=>Auth::guard('admin')->id(),'published_at'=>now()]);$campaign->update(['status'=>'published']);});
   cache()->forget('mobile_home_active_campaign'); return back()->with('success','Mobile homepage published.');
 }
 public function toggle(MobileHomeCampaign $campaign){$campaign->update(['status'=>$campaign->status==='disabled'?'draft':'disabled']);cache()->forget('mobile_home_active_campaign');return back()->with('success','Campaign status updated.');}
}
