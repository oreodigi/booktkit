<?php

namespace App\Services\Payments;

use App\Models\Payments\OrganizerPaymentProfile;

class SettlementRoutingService
{
    public function resolve(?OrganizerPaymentProfile $profile): array
    {
        if (!$profile) {
            return ['mode' => 'booktkit_managed', 'reason' => 'no_organizer_payment_profile'];
        }

        if ($profile->settlement_mode !== 'razorpay_split') {
            return ['mode' => 'booktkit_managed', 'reason' => 'organizer_prefers_booktkit_managed'];
        }

        if ($profile->canSplit()) {
            return ['mode' => 'razorpay_split', 'reason' => 'razorpay_route_eligible'];
        }

        return ['mode' => 'booktkit_managed', 'reason' => $this->fallbackReason($profile)];
    }

    private function fallbackReason(OrganizerPaymentProfile $profile): string
    {
        if ($profile->split_suspended) return 'razorpay_split_suspended';
        if (!$profile->split_enabled) return 'razorpay_split_not_enabled';
        if (empty($profile->razorpay_account_id) || empty($profile->razorpay_product_id)) return 'razorpay_account_not_ready';
        if ($profile->kyc_status !== 'activated') return 'razorpay_kyc_' . ($profile->kyc_status ?: 'pending');
        if (!in_array($profile->razorpay_status, ['active', 'activated'], true)) return 'razorpay_' . ($profile->razorpay_status ?: 'pending');
        return 'razorpay_direct_unavailable';
    }
}
