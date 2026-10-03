<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\Event\EventState;

class EventCountrySeeder extends Seeder
{
    public function run()
    {
        $totalStates = 10000;
        $maxStatesPerCountry = 500;
        $totalCountries = 10000;

        $statesCreated = 0;

        // প্রতিটি country কে সর্বোচ্চ ৫০০ states দিবে যতক্ষন ১০০০০ states পূর্ণ হয়
        for ($countryId = 1; $countryId <= $totalCountries; $countryId++) {
            $statesForThisCountry = min($maxStatesPerCountry, $totalStates - $statesCreated);

            if ($statesForThisCountry <= 0) {
                break; // মোট states পুর্ন হয়েছে
            }

            for ($i = 1; $i <= $statesForThisCountry; $i++) {
                EventState::create([
                    'language_id'   => 8,
                    'country_id'    => $countryId,
                    'status'        => 1,
                    'serial_number' => $statesCreated + 1,
                    'name'          => Str::random(10),
                    'slug'          => Str::slug(Str::random(10)),
                ]);
                $statesCreated++;
            }
        }
    }
}
