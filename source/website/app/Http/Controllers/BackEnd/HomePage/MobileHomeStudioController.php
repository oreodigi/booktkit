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
use App\Models\Event\EventCategory;
use App\Models\Event\EventContent;

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
 public function edit(MobileHomeCampaign $campaign){
   $campaign->load(['template','sections']);
   $categories=EventCategory::where('status',1)->orderBy('serial_number')->get();
   $events=EventContent::join('events','events.id','=','event_contents.event_id')->where('events.status',1)->where('events.end_date_time','>=',now())->select('event_contents.*','events.thumbnail','events.start_date')->orderBy('events.start_date')->limit(100)->get();
   return view('backend.home-page.mobile-home.edit',compact('campaign','categories','events'));
 }
 public function preview(MobileHomeCampaign $campaign){
   session(['mobile_home_preview_campaign'=>$campaign->id]);
   return redirect()->route('index')->with('mobile_home_preview',true);
 }
 public function clearPreview(){
   session()->forget('mobile_home_preview_campaign');
   return redirect()->route('index');
 }
 public function update(Request $r,MobileHomeCampaign $campaign){
   $campaign->update($r->validate(['name'=>'required|max:120','priority'=>'required|integer','starts_at'=>'nullable|date','ends_at'=>'nullable|date|after_or_equal:starts_at']));
   $design=$campaign->template->design ?: [];
   $design=array_merge($design,$r->input('design',[]));
   $campaign->template->update(['design'=>$design]);
   foreach($r->input('sections',[]) as $id=>$row){$s=$campaign->sections()->findOrFail($id);$s->update(['title'=>$row['title']??$s->title,'enabled'=>isset($row['enabled']),'position'=>(int)($row['position']??$s->position),'settings'=>array_filter($row['settings']??($s->settings?:[]),fn($v)=>$v!==null&&$v!=='')]);}
   return back()->with('success','Draft saved.');
 }
 public function publish(MobileHomeCampaign $campaign){
   DB::transaction(function()use($campaign){$campaign->load(['template','sections']);$v=(int)$campaign->versions()->max('version')+1;$snapshot=$campaign->toArray();MobileHomeVersion::create(['campaign_id'=>$campaign->id,'version'=>$v,'snapshot'=>$snapshot,'published_by'=>Auth::guard('admin')->id(),'published_at'=>now()]);$campaign->update(['status'=>'published']);});
   cache()->forget('mobile_home_active_campaign'); return back()->with('success','Mobile homepage published.');
 }
 public function toggle(MobileHomeCampaign $campaign){$campaign->update(['status'=>$campaign->status==='disabled'?'draft':'disabled']);cache()->forget('mobile_home_active_campaign');return back()->with('success','Campaign status updated.');}
}
