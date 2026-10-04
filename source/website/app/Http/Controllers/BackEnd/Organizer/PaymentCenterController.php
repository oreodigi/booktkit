<?php
namespace App\Http\Controllers\BackEnd\Organizer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Payments\OrganizerPaymentProfile;
use App\Models\Payments\PaymentOrder;
class PaymentCenterController extends Controller {
 public function index(){
  $id=Auth::guard('organizer')->id(); $profile=OrganizerPaymentProfile::firstOrCreate(['organizer_id'=>$id]);
  $orders=PaymentOrder::where('organizer_id',$id)->with(['transfers','refunds'])->latest()->paginate(20);
  $summary=['gross'=>(int)PaymentOrder::where('organizer_id',$id)->where('status','paid')->sum('ticket_amount'),
   'payable'=>(int)PaymentOrder::where('organizer_id',$id)->where('status','paid')->sum('organizer_amount'),
   'fees'=>(int)PaymentOrder::where('organizer_id',$id)->where('status','paid')->sum('platform_fee')];
  return view('organizer.payments.index',compact('profile','orders','summary'));
 }
}