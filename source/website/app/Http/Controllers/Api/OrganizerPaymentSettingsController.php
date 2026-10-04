<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payments\OrganizerPaymentProfile;
class OrganizerPaymentSettingsController extends Controller {
 public function show(Request $r){
  $o=$r->user('organizer_sanctum') ?? $r->user();
  $p=OrganizerPaymentProfile::firstOrCreate(['organizer_id'=>$o->id]);
  return response()->json(['success'=>true,'payment_profile'=>$p,'split_ready'=>$p->canSplit()]);
 }
 public function update(Request $r){
  $o=$r->user('organizer_sanctum') ?? $r->user();
  $data=$r->validate([
   'settlement_mode'=>'required|in:booktkit_managed,razorpay_split',
   'fee_bearer'=>'nullable|in:included,additional'
  ]);
  $p=OrganizerPaymentProfile::firstOrCreate(['organizer_id'=>$o->id]);
  if($data['settlement_mode']==='razorpay_split' && !$p->canSplit()) return response()->json(['success'=>false,'message'=>'Razorpay linked account must be active before split settlement can be enabled.'],422);
  $p->settlement_mode=$data['settlement_mode'];
  if(isset($data['fee_bearer'])) $p->fee_bearer=$data['fee_bearer'];
  $p->save();
  return response()->json(['success'=>true,'payment_profile'=>$p,'split_ready'=>$p->canSplit()]);
 }
}