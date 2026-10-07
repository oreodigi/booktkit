<?php
namespace App\Http\Controllers\BackEnd\Organizer;
use App\Http\Controllers\Controller;use App\Models\BoxOfficeShift;use App\Services\BoxOffice\BoxOfficeShiftService;use Illuminate\Http\Request;
class BoxOfficeShiftController extends Controller{
 public function index(){return view('organizer.box-office.shifts',['shifts'=>BoxOfficeShift::where('organizer_id',auth('organizer')->id())->with('staff')->latest()->paginate(50)]);}
 public function verify(Request $r,$id,BoxOfficeShiftService $svc){
  $d=$r->validate(['verification_note'=>'nullable|string|max:1000']);
  $staff=auth('staff')->user();
  $shift=BoxOfficeShift::where('organizer_id',auth('organizer')->id())->findOrFail($id);
  if($staff && (int)$shift->staff_id===(int)$staff->id) return back()->with('warning','A different supervisor must verify your shift.');
  $svc->verify($shift,$staff?(int)$staff->id:null,$staff?null:(int)auth('organizer')->id(),$d['verification_note']??'');
  return back()->with('success','Shift verified.');
 }
}