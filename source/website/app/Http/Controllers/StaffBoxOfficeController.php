<?php
namespace App\Http\Controllers;
use App\Models\Event;use App\Models\BoxOfficeSale;use App\Services\BoxOffice\BoxOfficeSaleService;use Illuminate\Http\Request;use Illuminate\Validation\Rule;use Illuminate\Support\Facades\Crypt;use App\Services\Tickets\TicketIssuanceService;
class StaffBoxOfficeController extends Controller{
 public function index(){ $s=auth('staff')->user();$ids=$s->assignments()->pluck('event_id');return view('staff.pos',['posSettings'=>\App\Services\BoxOffice\BoxOfficeSaleInput::settings((int)$s->organizer_id),'events'=>Event::where('organizer_id',$s->organizer_id)->whereIn('id',$ids)->where(function($q){$q->where('box_office_enabled',1)->orWhere('event_type','box_office');})->with(['boxOfficeLocations','tickets','dates'])->get()]);}
 public function store(Request $r,BoxOfficeSaleService $svc,\App\Services\BoxOffice\BoxOfficeSaleInput $input){
  $s=auth('staff')->user();
  if(!$s->assignedTo((int)$r->input('event_id'),(int)$r->input('location_id')))abort(403);
  $sale=$svc->sell($input->validate($r,(int)$s->organizer_id),(int)$s->organizer_id,(int)$s->id);
  return redirect()->route('staff.boxoffice.print',$sale->id);
 }
 public function print($id,TicketIssuanceService $issuance){$s=auth('staff')->user();$sale=BoxOfficeSale::where('organizer_id',$s->organizer_id)->where('staff_id',$s->id)->with('booking')->findOrFail($id);$issuedTickets=$issuance->ensureForBooking($sale->booking);return view('organizer.box-office.print',compact('sale','issuedTickets'));}
 public function reprint($id){
  $s=auth('staff')->user();
  $sale=BoxOfficeSale::where('organizer_id',$s->organizer_id)->where('staff_id',$s->id)->where('status','completed')->findOrFail($id);
  $sale->logs()->create(['action'=>'reprint','actor_staff_id'=>$s->id]);
  return redirect()->route('staff.boxoffice.print',$sale->id);
 }
}