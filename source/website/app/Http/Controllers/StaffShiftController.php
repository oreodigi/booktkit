<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;use App\Models\BoxOfficeShift;use App\Services\BoxOffice\BoxOfficeShiftService;use Illuminate\Http\Request;
class StaffShiftController extends Controller{
 public function index(){ $s=auth('staff')->user();return view('staff.shifts',['open'=>BoxOfficeShift::where('staff_id',$s->id)->where('status','open')->first(),'recent'=>BoxOfficeShift::where('staff_id',$s->id)->latest()->limit(10)->get(),'assignments'=>$s->assignments()->with(['event','location'])->get()]);}
 public function open(Request $r,BoxOfficeShiftService $svc){$s=auth('staff')->user();$d=$r->validate(['event_id'=>'required|integer','location_id'=>'required|integer','opening_cash'=>'required|numeric|min:0']);if(!$s->assignedTo((int)$d['event_id'],(int)$d['location_id']))abort(403);$svc->open($s->organizer_id,$s->id,$d['event_id'],$d['location_id'],(int)round($d['opening_cash']*100));return back()->with('success','Shift opened.');}
 public function close(Request $r,$id,BoxOfficeShiftService $svc){$s=auth('staff')->user();$d=$r->validate(['declared_closing_cash'=>'required|numeric|min:0']);$shift=BoxOfficeShift::where('staff_id',$s->id)->findOrFail($id);$svc->close($shift,(int)round($d['declared_closing_cash']*100));return back()->with('success','Shift closed for verification.');}
}