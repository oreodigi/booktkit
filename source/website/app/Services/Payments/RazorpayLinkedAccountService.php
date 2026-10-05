<?php
namespace App\Services\Payments;
use App\Models\Payments\OrganizerPaymentProfile;
use App\Models\PaymentGateway\OnlineGateway;
use Illuminate\Support\Facades\Http;
class RazorpayLinkedAccountService {
 private function http(){
  $g=OnlineGateway::whereKeyword('razorpay')->firstOrFail(); $v=json_decode($g->information,true);
  return Http::withBasicAuth((string)$v['key'],(string)$v['secret'])->acceptJson()->asJson()->timeout(30);
 }
 public function onboard(OrganizerPaymentProfile $p,string $bankAccount,bool $accepted): OrganizerPaymentProfile {
  if(!$accepted) throw new \InvalidArgumentException('Route terms must be accepted.');
  if(!$p->razorpay_account_id){
   $r=$this->http()->post('https://api.razorpay.com/v2/accounts',[
    'email'=>$p->contact_email,'phone'=>$p->contact_phone,'legal_business_name'=>$p->legal_business_name,'business_type'=>$p->business_type,'type'=>'route',
    'profile'=>['category'=>$p->business_category,'subcategory'=>$p->business_subcategory],
    'legal_info'=>array_filter(['pan'=>$p->pan,'gst'=>$p->gstin]),'notes'=>['reference_id'=>'organizer_'.$p->organizer_id]
   ])->throw()->json(); $p->razorpay_account_id=$r['id']; $this->remember($p,'account',$r); $p->save();
  }
  if(!$p->razorpay_stakeholder_id){
   $r=$this->http()->post('https://api.razorpay.com/v2/accounts/'.$p->razorpay_account_id.'/stakeholders',[
    'name'=>$p->contact_name,'email'=>$p->contact_email,'relationship'=>['director'=>true,'executive'=>true],
    'phone'=>['primary'=>$p->contact_phone],'kyc'=>['pan'=>$p->stakeholder_pan ?: $p->pan]
   ])->throw()->json(); $p->razorpay_stakeholder_id=$r['id']; $this->remember($p,'stakeholder',$r); $p->save();
  }
  if(!$p->razorpay_product_id){
   $r=$this->http()->post('https://api.razorpay.com/v2/accounts/'.$p->razorpay_account_id.'/products',['product_name'=>'route','tnc_accepted'=>true,'applicable_identified'=>true])->throw()->json();
   $p->razorpay_product_id=$r['id']; $this->remember($p,'product',$r); $p->save();
  }
  $bank=$this->http()->patch('https://api.razorpay.com/v2/accounts/'.$p->razorpay_account_id.'/products/'.$p->razorpay_product_id,[
   'settlements'=>['account_number'=>$bankAccount,'ifsc_code'=>$p->bank_ifsc,'beneficiary_name'=>$p->bank_account_holder],'tnc_accepted'=>true
  ])->throw()->json(); $this->remember($p,'settlements',$bank);
  $p->route_terms_accepted_at=$p->route_terms_accepted_at ?: now(); $p->save();
  return $this->sync($p);
 }
 public function sync(OrganizerPaymentProfile $p): OrganizerPaymentProfile {
  if(!$p->razorpay_account_id||!$p->razorpay_product_id) return $p;
  $a=$this->http()->get('https://api.razorpay.com/v2/accounts/'.$p->razorpay_account_id)->throw()->json();
  $r=$this->http()->get('https://api.razorpay.com/v2/accounts/'.$p->razorpay_account_id.'/products/'.$p->razorpay_product_id)->throw()->json();
  $status=$r['activation_status']??$r['status']??$a['status']??'under_review';
  $p->razorpay_status=$status;
  $p->kyc_status=match($status){'activated','active'=>'activated','needs_clarification'=>'needs_clarification','rejected'=>'rejected','suspended'=>'suspended',default=>'under_review'};
  $p->split_enabled=$p->kyc_status==='activated';
  if($p->split_enabled&&!$p->activated_at)$p->activated_at=now();
  $this->remember($p,'account_sync',$a); $this->remember($p,'product_sync',$r); $p->save(); return $p->fresh();
 }
 private function remember(OrganizerPaymentProfile $p,string $step,array $r): void { array_walk_recursive($r,function(&$v,$k){if(in_array(strtolower((string)$k),['pan','gst','gstin','account_number'],true))$v='[REDACTED]';}); $m=$p->metadata?:[];$m['route'][$step]=['at'=>now()->toIso8601String(),'response'=>$r];$p->metadata=$m; }
}