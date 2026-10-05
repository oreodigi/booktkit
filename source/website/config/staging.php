<?php
return [
    // Only honored on the exact staging hostname, with APP_ENV=staging.
    'recaptcha_test_mode' => env('BOOKTKIT_RECAPTCHA_TEST_MODE', false),
    'database' => 'booktkit_stage',
    'hostname' => 'test.booktkit.com',
];
