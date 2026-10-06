<?php
namespace App\Http\Controllers\BackEnd;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organizer;
use App\Models\Payments\OrganizerPaymentProfile;
use App\Models\Payments\PaymentFeeRule;
use App\Models\Payments\EventAdditionalFee;
use App\Models\Payments\PaymentOrder;
class PaymentFinanceController extends Controller {
 public function index(Request $r){
  $q=PaymentOrder::with(['transfers','refunds','feeLines'])->latest();
  if($r->filled('status'))$q->where('status',$r->status);if($r->filled('organizer_id'))$q->where('organizer_id',$r->organizer_id);if($r->filled('sales_channel'))$q->where('sales_channel',$r->sales_channel);
  $orders=$q->paginate(25)->withQueryString();
  $summary=['paid'=>(int)PaymentOrder::where('status','paid')->sum('customer_total'),'platform'=>(int)PaymentOrder::where('status','paid')->sum('booktkit_revenue'),'organizer'=>(int)PaymentOrder::where('status','paid')->sum('organizer_amount'),'refunded'=>(int)PaymentOrder::sum('refunded_amount')];
  $feeRules=PaymentFeeRule::orderByDesc('priority')->orderByDesc('id')->get();$additionalFees=EventAdditionalFee::latest()->limit(100)->get();$organizers=Organizer::orderBy('id')->get(['id','username','email']);
  return view('backend.payments.finance',compact('orders','summary','feeRules','additionalFees','organizers'));
 }
 public function storeFeeRule(Request $r){$d=$this->validateFeeRule($r);$d['fixed_amount']=(int)round(((float)$d['fixed_amount_rupees'])*100);unset($d['fixed_amount_rupees']);$d['is_active']=$r->boolean('is_active');PaymentFeeRule::create($d);return back()->with('success','Payment fee rule created.');}
 public function updateFeeRule(Request $r,$id){$rule=PaymentFeeRule::findOrFail($id);$d=$this->validateFeeRule($r);$d['fixed_amount']=(int)round(((float)$d['fixed_amount_rupees'])*100);unset($d['fixed_amount_rupees']);$d['is_active']=$r->boolean('is_active');$rule->update($d);return back()->with('success','Payment fee rule updated. New orders will use the updated rule.'');}
 public function destroyFeeRule($id){$rule=PaymentFeeRule::findOrFail($id);$rule->update(['is_active'=>false]);return back()->with('success','Payment fee rule disabled. Existing order snapshots are unchanged.');}
 private function validateFeeRule(Request $r):array{return $r->validate(['name'=>'required|string|max:120','scope'=>'required|in:global,organizer,event','event_id'=>'nullable|required_unless:scope,global|integer|min:1','event_type'=>'nullable|in:online,venue,box_office','sales_channel'=>'nullable|in:web,mobile,pos','percentage'=>'required|numeric|min:0|max:100','fixed_amount_rupees'=>'required|numeric|min:0','fee_bearer'=>'required|in:customer,organizer','priority'=>'required|integer|min:0|max:100000','effective_from'=>'nullable|date','effective_until'=>'nullable|date|after:effective_from']);}
 public function storeAdditionalFee(Request $r){$d=$r->validate(['event_id'=>'required|integer|exists:events,id','code'=>'required|string|max:64','name'=>'required|string|max:120','calculation_type'=>'required|in:fixed_per_order,fixed_per_ticket,percentage','fixed_amount_rupees'=>'required|numeric|min:0','percentage'=>'required|numeric|min:0|max:100','fee_bearer'=>'required|in:customer,organizer','sales_channels'=>'nullable|array','sales_channels.*'=>'in:web,mobile,pos']);$d['fixed_amount']=(int)round(((float)$d['fixed_amount_rupees'])*100);unset($d['fixed_amount_rupees'],$d['sales_channels']);$d['is_mandatory']=$r->boolean('mandatory');$d['is_taxable']=$r->boolean('taxable');$d['application']=$d['calculation_type']==='fixed_per_ticket'?'per_ticket':'per_order';$d['calculation_type']=$d['calculation_type']==='percentage'?'percentage':'fixed';$d['is_active']=true;EventAdditionalFee::updateOrCreate(['event_id'=>$d['event_id'],'code'=>$d['code']],$d);return back()->with('success','Additional fee saved.');}
 public function disableAdditionalFee($id){EventAdditionalFee::findOrFail($id)->update(['is_active'=>false]);return back()->with('success','Additional fee disabled for new orders.');}
 public function organizers(){$profiles=OrganizerPaymentProfile::with('organizer')->latest()->paginate(25);return view('backend.payments.organizers',compact('profiles'));}
 public function updateOrganizer(Request $r,$id){$p=OrganizerPaymentProfile::firstOrCreate(['organizer_id'=>$id]);$d=$r->validate(['preferred_settlement_mode'=>'required|in:booktkit_managed,razorpay_split','razorpay_account_id'=>'nullable|string|max:100','razorpay_status'=>'required|in:not_started,details_submitted,razorpay_pending,kyc_pending,bank_verification_pending,active,activated,restricted,suspended,rejected','gst_verified'=>'nullable|boolean','gstin'=>'nullable|string|max:20','split_enabled'=>'nullable|boolean']);$d['gst_verified']=$r->boolean('gst_verified');$d['split_enabled']=$r->boolean('split_enabled');$d['settlement_mode']=$d['preferred_settlement_mode'];$p->update($d);return back()->with('success',$d['preferred_settlement_mode']==='razorpay_split'&&!$p->fresh()->canSplit()?'Direct settlement preferred, but sales will remain BookTKIT Managed until Razorpay KYC/Route is active.':'Organizer settlement preference updated.');}
}