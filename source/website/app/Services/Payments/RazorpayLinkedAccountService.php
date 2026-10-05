<?php
namespace App\Services\Payments;
use App\Models\Payments\OrganizerPaymentProfile;
use Illuminate\Support\Facades\Http;
class RazorpayLinkedAccountService {
 private function http(){ return Http::withBasicAuth((string)config('services.razorpay.key'),(string)config('services.razorpay.api_secret'))->acceptJson()->asJson()->timeout(30); }
 public function sync(OrganizerPaymentProfile $p): OrganizerPaymentProfile {
  if(!$p->razorpay_account_id||!$p->razorpay_product_id) return $p;
  $a=$this->http()->get('https://api.razorpay.com/v2/accounts/'.$p->razorpay_account_id)->throw()->json();
  $r=$this->http()->get('https://api.razorpay.com/v2/accounts/'.$p->razorpay_account_id.'/products/'.$p->razorpay_product_id)->throw()->json();
  $status=$r['activation_status']??$r['status']??$a['status']??'under_review';
  $p->razorpay_status=$status;
  $p->kyc_status=match($status){'activated','active'=>'activated','needs_clarification'=>'needs_clarification','rejected'=>'rejected','suspended'=>'suspended',default=>'under_review'};
  $p->split_enabled=$p->kyc_status==='activated';
  if($p->split_enabled&&!$p->activated_at)$p->activated_at=now();
  $p->save(); return $p->fresh();
 }
}