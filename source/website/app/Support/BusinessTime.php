<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Event dates and times are entered by organizers in local (venue) time and stored without a zone.
 * Compare them in the business timezone, whatever the server's APP_TIMEZONE is.
 */
class BusinessTime
{
    public static function zone(): string
    {
        return (string) config('booktkit.business_timezone', 'Asia/Kolkata');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::zone());
    }

    public static function parse(string $value): Carbon
    {
        return Carbon::parse($value, self::zone());
    }
}
