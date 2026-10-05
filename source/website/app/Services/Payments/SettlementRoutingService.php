<?php
namespace App\Services\Payments;
use App\Models\Payments\OrganizerPaymentProfile;
class SettlementRoutingService {
 public function decide(?OrganizerPaymentProfile $profile,string $channel='web'): array {
  $preferred=$profile->preferred_settlement_mode ?? $profile->settlement_mode ?? 'booktkit_managed';
  if($preferred!=='razorpay_split') return ['mode'=>'booktkit_managed','reason'=>'organizer_prefers_managed'];
  if(!$profile) return ['mode'=>'booktkit_managed','reason'=>'payment_profile_missing'];
  if($profile->split_suspended) return ['mode'=>'booktkit_managed','reason'=>'direct_settlement_suspended'];
  if(!$profile->canSplit()) return ['mode'=>'booktkit_managed','reason'=>'razorpay_kyc_not_active'];
  return ['mode'=>'razorpay_split','reason'=>'razorpay_route_eligible'];
 }
}