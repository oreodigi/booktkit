<?php
namespace App\Http\Controllers\BackEnd\Organizer;
use App\Http\Controllers\Controller;use App\Models\BoxOfficeSale;use App\Models\BoxOfficeVoidRequest;use App\Models\Event;use App\Services\BoxOffice\BoxOfficeLedgerService;use App\Services\BoxOffice\BoxOfficeSaleService;use App\Services\BoxOffice\LockedTicketInventoryService;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Illuminate\Validation\Rule;use Illuminate\Support\Facades\Crypt;use Illuminate\Support\Facades\Storage;use App\Services\Tickets\TicketIssuanceService;
class BoxOfficeController extends Controller{
 public function index(){
  $oid=auth('organizer')->id(); $staff=auth('staff')->user();
  $events=Event::where('organizer_id',$oid)->where(function($q){$q->where('box_office_enabled',1)->orWhere('event_type','box_office');})
   ->when($staff,fn($q)=>$q->whereIn('id',$staff->assignments()->pluck('event_id')))
   ->with(['boxOfficeLocations','tickets','dates','information','passProducts.dates'])->orderByDesc('id')->get();
  return view('organizer.box-office.pos',['posSettings'=>\App\Services\BoxOffice\BoxOfficeSaleInput::settings((int)$oid),'events'=>$events]);
 }
 public function store(Request $r,BoxOfficeSaleService $svc,\App\Services\BoxOffice\BoxOfficeSaleInput $input){
  $oid=(int)auth('organizer')->id(); $staff=auth('staff')->user();
  // Team members selling from this workspace: assignment-scoped, attributed to them and their open shift.
  if($staff && !$staff->assignedTo((int)$r->input('event_id'),(int)$r->input('location_id'))) abort(403,'You are not assigned to this event counter.');
  $sale=$svc->sell($input->validate($r,$oid),$oid,$staff?(int)$staff->id:null);
  return redirect()->route('organizer.boxoffice.print',$sale->id);
 }
 public function print($id,TicketIssuanceService $issuance){$sale=BoxOfficeSale::where('organizer_id',auth('organizer')->id())->with('booking')->findOrFail($id);$issuedTickets=$issuance->ensureForBooking($sale->booking);return view('organizer.box-office.print',compact('sale','issuedTickets'));}
 public function reprint($id){
  $oid=auth('organizer')->id(); $staff=auth('staff')->user();
  $sale=BoxOfficeSale::where('organizer_id',$oid)->where('status','completed')->findOrFail($id);
  $sale->logs()->create(['action'=>'reprint','actor_staff_id'=>$staff?$staff->id:null,'actor_organizer_id'=>$staff?null:$oid]);
  return redirect()->route('organizer.boxoffice.print',$sale->id);
 }
 public function requestVoid(Request $r,$id,\App\Services\BoxOffice\BoxOfficeVoidService $voids){
  $oid=auth('organizer')->id(); $staff=auth('staff')->user();
  $sale=BoxOfficeSale::where('organizer_id',$oid)->where('status','completed')->findOrFail($id);
  $d=$r->validate(['reason'=>'required|string|max:1000']);
  $voids->request($sale,$d['reason'],$staff?(int)$staff->id:null,$oid);
  return back()->with('success','Void request created.');
 }
 public function approveVoid($id,\App\Services\BoxOffice\BoxOfficeVoidService $voids){
  $staff=auth('staff')->user();
  try { $voids->approve((int)$id,(int)auth('organizer')->id(),$staff?(int)$staff->id:null); }
  catch(\Illuminate\Validation\ValidationException $e){ return back()->with('warning',collect($e->errors())->flatten()->first()); }
  return back()->with('success','Sale voided: stock returned and its tickets cancelled.');
 }
}