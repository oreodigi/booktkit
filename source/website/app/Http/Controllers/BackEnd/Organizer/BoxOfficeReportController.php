<?php
namespace App\Http\Controllers\BackEnd\Organizer;
use App\Http\Controllers\Controller;
use App\Models\BoxOfficeSale;
use App\Models\BoxOfficeShift;
use App\Models\BoxOfficeSaleLog;
use App\Models\Event\Booking;
use App\Models\Event\IssuedTicket;
use Illuminate\Http\Request;
class BoxOfficeReportController extends Controller {
 private function sales(Request $r,int $oid){return BoxOfficeSale::where('organizer_id',$oid)->when($r->event_id,fn($q,$v)=>$q->where('event_id',$v))->when($r->location_id,fn($q,$v)=>$q->where('location_id',$v))->when($r->staff_id,fn($q,$v)=>$q->where('staff_id',$v))->when($r->payment_method,fn($q,$v)=>$q->where('payment_method',$v))->when($r->from,fn($q,$v)=>$q->whereDate('created_at','>=',$v))->when($r->to,fn($q,$v)=>$q->whereDate('created_at','<=',$v));}
 public function index(Request $r){$oid=auth('organizer')->id();$q=$this->sales($r,$oid);$all=(clone $q)->get();$completed=$all->where('status','completed');$summary=['sales_count'=>$all->count(),'gross'=>$completed->sum('customer_total'),'platform_fee'=>$completed->sum('platform_fee'),'organizer_amount'=>$completed->sum('organizer_amount'),'voids'=>$all->where('status','voided')->count()];$payments=$completed->groupBy('payment_method')->map(fn($g)=>['count'=>$g->count(),'total'=>$g->sum('customer_total')]);$shifts=BoxOfficeShift::where('organizer_id',$oid)->when($r->event_id,fn($x,$v)=>$x->where('event_id',$v))->get();$online=Booking::where('organizer_id',$oid)->where('booking_source','online')->where('paymentStatus','completed')->count();$box=Booking::where('organizer_id',$oid)->where('booking_source','box_office')->where('paymentStatus','completed')->count();$admissions=IssuedTicket::where('organizer_id',$oid)->whereNotNull('checked_in_at')->count();$reprints=BoxOfficeSaleLog::whereIn('sale_id',$all->pluck('id'))->where('action','reprint')->count();return view('organizer.box-office.report',compact('summary','payments','shifts','online','box','admissions','reprints'));}
}