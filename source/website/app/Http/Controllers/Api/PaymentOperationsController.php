<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Payments\PaymentOrder;
use App\Models\Payments\OrganizerPaymentProfile;
use App\Services\Payments\RazorpayRouteService;
use App\Services\Payments\PaymentReconciliationService;
class PaymentOperationsController extends Controller {
 public function reconcile($uuid,PaymentReconciliationService $svc){ return response()->json($svc->reconcile(PaymentOrder::where('uuid',$uuid)->firstOrFail())); }
 public function refund(Request $r,$uuid,RazorpayRouteService $rz){
  $data=$r->validate(['amount'=>'required|integer|min:1','reason'=>'nullable|string|max:255']);
  return DB::transaction(function() use($uuid,$data,$rz){ $o=PaymentOrder::where('uuid',$uuid)->lockForUpdate()->firstOrFail();
   $refund=$rz->refund($o,(int)$data['amount'],$data['reason']??'admin_requested');
   return response()->json(['success'=>true,'refund'=>$refund]); });
 }
 public function retryTransfer($uuid,RazorpayRouteService $rz){
  $o=PaymentOrder::where('uuid',$uuid)->firstOrFail(); $p=OrganizerPaymentProfile::where('organizer_id',$o->organizer_id)->first();
  abort_unless($o->settlement_mode==='razorpay_split' && $p && $p->canSplit(),422,'Organizer is not eligible for Razorpay split settlement.');
  return response()->json(['success'=>true,'transfer'=>$rz->transferToOrganizer($o,$p->razorpay_account_id)]);
 }
}