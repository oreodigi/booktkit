<?php

return [
    // Shared secret for the legacy HTTP cron endpoints (/send-ticket, /check-payment, ...).
    // Leave empty to disable them; use `php artisan schedule:run` from crontab instead.
    'cron_http_token' => env('CRON_HTTP_TOKEN', ''),

    // How long a just-completed checkout may view its confirmation page without logging in.
    'confirmation_access_minutes' => (int) env('BOOKTKIT_CONFIRMATION_ACCESS_MINUTES', 120),

    // Captured Razorpay payments that could not become bookings are retried by
    // `payments:recover-unfinalized`. When enabled, orders still failing after the grace
    // period (and 3 attempts) are refunded in full automatically.
    'auto_refund_unfulfilled' => (bool) env('BOOKTKIT_AUTO_REFUND_UNFULFILLED', false),
    'unfulfilled_refund_after_minutes' => (int) env('BOOKTKIT_UNFULFILLED_REFUND_AFTER_MINUTES', 30),

    // Hours after the last session ends during which tickets can still be scanned (late exits).
    'admission_grace_hours' => (int) env('BOOKTKIT_ADMISSION_GRACE_HOURS', 6),
];
