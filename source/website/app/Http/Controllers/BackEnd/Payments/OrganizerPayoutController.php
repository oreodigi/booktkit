<?php
namespace App\Http\Controllers\BackEnd\Payments;
use App\Http\Controllers\Controller;
use App\Jobs\Payments\TransferOrganizerPayment;
use App\Models\Payments\OrganizerPaymentProfile;
use App\Models\Payments\PaymentTransfer;
use App\Services\Payments\RazorpayLinkedAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class OrganizerPayoutController extends Controller {
 public function index(Request $r){
  $q=OrganizerPaymentProfile::with('organizer')->latest();
  if($r->filled('status'))$q->where('kyc_status',$r->status);
  $profiles=$q->paginate(25); $holdDays=(int)(DB::table('payment_settings')->where('key','transfer_hold_days')->value('value')??2);
  return view('backend.payments.organizer-payouts',compact('profiles','holdDays'));
 }
 public function update(Request $r,int $id){
  $p=OrganizerPaymentProfile::findOrFail($id); $d=$r->validate([
   'fee_type'=>'required|in:percentage,fixed,hybrid','fee_value'=>'required|numeric|min:0','fee_fixed'=>'required|numeric|min:0',
   'fee_bearer'=>'required|in:included,additional','split_suspended'=>'nullable|boolean']);
  $p->fill($d);$p->split_suspended=(bool)($d['split_suspended']??false);$p->save();return back()->with('success','Payout settings updated.');
 }
 public function sync(int $id,RazorpayLinkedAccountService $s){$s->sync(OrganizerPaymentProfile::findOrFail($id));return back()->with('success','Razorpay status synced.');}
 public function retryTransfer(int $id){$t=PaymentTransfer::findOrFail($id);$t->increment('attempts');TransferOrganizerPayment::dispatch($t->payment_order_id);return back()->with('success','Transfer retry queued.');}
 public function holdDays(Request $r){$d=$r->validate(['transfer_hold_days'=>'required|integer|min:0|max:90']);DB::table('payment_settings')->updateOrInsert(['key'=>'transfer_hold_days'],['value'=>(string)$d['transfer_hold_days'],'updated_at'=>now(),'created_at'=>now()]);return back()->with('success','Transfer hold updated.');}
 public function transfers(Request $r){$transfers=PaymentTransfer::with('order')->latest()->paginate(50);return view('backend.payments.transfers',compact('transfers'));}
 public function export(){return response()->streamDownload(function(){ $h=fopen('php://output','w');fputcsv($h,['Transfer','Order','Organizer','Amount','Status','Hold release']);PaymentTransfer::orderBy('id')->chunk(500,function($rows)use($h){foreach($rows as $t)fputcsv($h,[$t->gateway_transfer_id,$t->payment_order_id,$t->organizer_id,$t->amount,$t->status,$t->hold_release_at]);});fclose($h);},'booktkit-transfers.csv',['Content-Type'=>'text/csv']);}
}