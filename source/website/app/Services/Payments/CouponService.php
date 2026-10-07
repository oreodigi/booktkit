<?php

namespace App\Services\Payments;

use App\Models\Event\Coupon;
use Carbon\Carbon;

/**
 * Server-side coupon evaluation for Payments V2. Mirrors the checkout rules (active window,
 * event restriction, fixed rupees or percentage of the amount after early-bird) in paise.
 */
class CouponService
{
    /** @return array{amount:int,code:?string,coupon_id:?int} */
    public function discount(?string $code, int $eventId, int $amountAfterEarlyBirdPaise): array
    {
        $none = ['amount' => 0, 'code' => null, 'coupon_id' => null];
        $code = trim((string) $code);
        if ($code === '' || $amountAfterEarlyBirdPaise <= 0) return $none;
        $coupon = Coupon::where('code', $code)->first();
        if (!$coupon) return $none;
        $now = Carbon::now();
        if ($now->lt(Carbon::parse($coupon->start_date)) || $now->gte(Carbon::parse($coupon->end_date))) return $none;
        $events = json_decode((string) $coupon->events, true);
        if (!empty($events) && !in_array($eventId, array_map('intval', $events), true)) return $none;
        $amount = $coupon->type === 'fixed'
            ? (int) round(((float) $coupon->value) * 100)
            : (int) round($amountAfterEarlyBirdPaise * ((float) $coupon->value) / 100);
        return ['amount' => max(0, min($amount, $amountAfterEarlyBirdPaise)), 'code' => $coupon->code, 'coupon_id' => (int) $coupon->id];
    }
}
