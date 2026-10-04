<?php
namespace App\Http\Controllers\BackEnd;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organizer;
use App\Models\Payments\OrganizerPaymentProfile;
use App\Models\Payments\PaymentOrder;
class PaymentFinanceController extends Controller {
 public function index(Request $r){
  $q=PaymentOrder::with(['transfers','refunds'])->latest();
  if($r->filled('status')) $q->where('status',$r->status);
  if($r->filled('organizer_id')) $q->where('organizer_id',$r->organizer_id);
  $orders=$q->paginate(25)->withQueryString();
  $summary=['paid'=>(int)PaymentOrder::where('status','paid')->sum('customer_total'),'platform'=>(int)PaymentOrder::where('status','paid')->sum('platform_fee'),
   'organizer'=>(int)PaymentOrder::where('status','paid')->sum('organizer_amount'),'refunded'=>(int)PaymentOrder::sum('refunded_amount')];
  return view('backend.payments.finance',compact('orders','summary'));
 }
 public function organizers(){
  $profiles=OrganizerPaymentProfile::with('organizer')->latest()->paginate(25);
  return view('backend.payments.organizers',compact('profiles'));
 }
 public function updateOrganizer(Request $r,$id){
  $p=OrganizerPaymentProfile::firstOrCreate(['organizer_id'=>$id]);
  $d=$r->validate(['settlement_mode'=>'required|in:booktkit_managed,razorpay_split','razorpay_account_id'=>'nullable|string|max:100',
   'razorpay_status'=>'required|in:not_started,details_submitted,razorpay_pending,kyc_pending,bank_verification_pending,active,restricted,suspended,rejected',
   'gst_verified'=>'nullable|boolean','gstin'=>'nullable|string|max:20','fee_type'=>'required|in:percentage,fixed,hybrid','fee_value'=>'required|numeric|min:0|max:100',
   'fee_fixed'=>'required|numeric|min:0','fee_bearer'=>'required|in:included,additional','split_enabled'=>'nullable|boolean']);
  $d['gst_verified']=$r->boolean('gst_verified'); $d['split_enabled']=$r->boolean('split_enabled');
  if($d['split_enabled'] && ($d['razorpay_status']!=='active' || empty($d['razorpay_account_id']))) return back()->with('error','Split settlement requires an active Razorpay linked account.');
  $p->update($d); return back()->with('success','Organizer payment settings updated.');
 }
}