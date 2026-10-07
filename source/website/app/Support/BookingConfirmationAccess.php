<?php

namespace App\Support;

use App\Models\Event\Booking;
use Illuminate\Support\Facades\Auth;

/**
 * Decides who may open the post-checkout confirmation page (which shows live ticket QR codes).
 * Allowed: the customer who owns the booking, or the browser session that just completed it.
 */
class BookingConfirmationAccess
{
    private const KEY = 'booktkit_confirmed_bookings';

    public static function grant($booking): void
    {
        if (!$booking || !function_exists('session') || !app()->bound('session')) return;
        $id = is_object($booking) ? (int) $booking->id : (int) $booking;
        if ($id <= 0) return;
        $grants = array_filter((array) session(self::KEY, []), fn ($expires) => (int) $expires > time());
        $grants[$id] = time() + 60 * max(1, (int) config('booktkit.confirmation_access_minutes', 120));
        session([self::KEY => $grants]);
    }

    public static function allows(Booking $booking): bool
    {
        $customer = Auth::guard('customer')->user();
        if ($customer && is_numeric($booking->customer_id) && (int) $booking->customer_id === (int) $customer->id) {
            return true;
        }
        $grants = (array) session(self::KEY, []);
        return isset($grants[$booking->id]) && (int) $grants[$booking->id] > time();
    }
}
