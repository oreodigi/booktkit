<?php
namespace App\Http\Controllers\BackEnd\Organizer;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\OrganizerStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
class StaffController extends Controller {
 public function index(){ $oid=auth('organizer')->id();return view('organizer.staff.index',['staff'=>OrganizerStaff::where('organizer_id',$oid)->with('assignments')->latest()->get(),'events'=>Event::where('organizer_id',$oid)->where('box_office_enabled',1)->with('boxOfficeLocations')->get(),'roles'=>config('staff.roles')]);}
 public function store(Request $r){$oid=auth('organizer')->id();$d=$r->validate(['name'=>'required|string|max:120','username'=>['required','string','max:80',Rule::unique('organizer_staff')->where(fn($q)=>$q->where('organizer_id',$oid))],'email'=>['nullable','email','max:190',Rule::unique('organizer_staff')->where(fn($q)=>$q->where('organizer_id',$oid))],'phone'=>'nullable|string|max:30','password'=>'required|string|min:8','role'=>['required',Rule::in(array_keys(config('staff.roles')))],'event_id'=>'required|integer','box_office_location_id'=>'nullable|integer']);$event=$this->event($d['event_id'],$oid);$this->validateLocation($event,$d['box_office_location_id']??null,$d['role']);DB::transaction(function()use($d,$oid){$s=OrganizerStaff::create(array_merge($d,['organizer_id'=>$oid,'password'=>Hash::make($d['password']),'must_change_password'=>true]));$s->assignments()->create(['event_id'=>$d['event_id'],'box_office_location_id'=>$d['box_office_location_id']??null]);});return back()->with('success','Staff member created.');}
 public function update(Request $r,$id){$oid=auth('organizer')->id();$s=OrganizerStaff::where('organizer_id',$oid)->findOrFail($id);$d=$r->validate(['name'=>'required|string|max:120','role'=>['required',Rule::in(array_keys(config('staff.roles')))],'active'=>'nullable|boolean','event_id'=>'required|integer','box_office_location_id'=>'nullable|integer']);$event=$this->event($d['event_id'],$oid);$this->validateLocation($event,$d['box_office_location_id']??null,$d['role']);DB::transaction(function()use($s,$d){$s->update(['name'=>$d['name'],'role'=>$d['role'],'active'=>(bool)($d['active']??false)]);$s->assignments()->delete();$s->assignments()->create(['event_id'=>$d['event_id'],'box_office_location_id'=>$d['box_office_location_id']??null]);if(!$s->active)$s->tokens()->delete();});return back()->with('success','Staff member updated.');}
 private function event($id,$oid){return Event::where('organizer_id',$oid)->where('box_office_enabled',1)->findOrFail($id);}
 private function validateLocation($event,$location,$role){if(in_array($role,['sales_agent','box_office_supervisor'],true)&&!$location)abort(422,'A box office location is required for this role.');if($location&&!$event->boxOfficeLocations()->whereKey($location)->exists())abort(422,'Invalid box office location.');}
}