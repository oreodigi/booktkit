<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        $locations = {
  'Andhra Pradesh' => [
    'Visakhapatnam',
    'Vijayawada',
    'Guntur',
    'Tirupati',
    'Nellore',
    'Kurnool',
    'Rajahmundry',
    'Kakinada'
  ],
  'Arunachal Pradesh' => [
    'Itanagar',
    'Naharlagun',
    'Pasighat',
    'Tawang',
    'Ziro'
  ],
  'Assam' => [
    'Guwahati',
    'Dibrugarh',
    'Silchar',
    'Jorhat',
    'Tezpur',
    'Nagaon',
    'Tinsukia'
  ],
  'Bihar' => [
    'Patna',
    'Gaya',
    'Muzaffarpur',
    'Bhagalpur',
    'Darbhanga',
    'Purnia',
    'Bihar Sharif'
  ],
  'Chhattisgarh' => [
    'Raipur',
    'Bhilai',
    'Durg',
    'Bilaspur',
    'Korba',
    'Raigarh',
    'Jagdalpur'
  ],
  'Goa' => [
    'Panaji',
    'Margao',
    'Vasco da Gama',
    'Mapusa',
    'Ponda'
  ],
  'Gujarat' => [
    'Ahmedabad',
    'Surat',
    'Vadodara',
    'Rajkot',
    'Gandhinagar',
    'Bhavnagar',
    'Jamnagar',
    'Junagadh',
    'Anand'
  ],
  'Haryana' => [
    'Gurugram',
    'Faridabad',
    'Panipat',
    'Ambala',
    'Karnal',
    'Hisar',
    'Rohtak',
    'Sonipat',
    'Panchkula'
  ],
  'Himachal Pradesh' => [
    'Shimla',
    'Dharamshala',
    'Manali',
    'Solan',
    'Mandi',
    'Kullu',
    'Una'
  ],
  'Jharkhand' => [
    'Ranchi',
    'Jamshedpur',
    'Dhanbad',
    'Bokaro',
    'Deoghar',
    'Hazaribagh'
  ],
  'Karnataka' => [
    'Bengaluru',
    'Mysuru',
    'Mangaluru',
    'Hubballi',
    'Dharwad',
    'Belagavi',
    'Shivamogga',
    'Davangere',
    'Ballari',
    'Udupi'
  ],
  'Kerala' => [
    'Thiruvananthapuram',
    'Kochi',
    'Kozhikode',
    'Thrissur',
    'Kollam',
    'Kannur',
    'Alappuzha',
    'Kottayam',
    'Palakkad'
  ],
  'Madhya Pradesh' => [
    'Indore',
    'Bhopal',
    'Jabalpur',
    'Gwalior',
    'Ujjain',
    'Sagar',
    'Satna',
    'Rewa'
  ],
  'Maharashtra' => [
    'Mumbai',
    'Pune',
    'Nagpur',
    'Nashik',
    'Thane',
    'Navi Mumbai',
    'Aurangabad',
    'Kolhapur',
    'Solapur',
    'Amravati',
    'Nanded'
  ],
  'Manipur' => [
    'Imphal',
    'Thoubal',
    'Churachandpur',
    'Bishnupur'
  ],
  'Meghalaya' => [
    'Shillong',
    'Tura',
    'Jowai',
    'Nongpoh'
  ],
  'Mizoram' => [
    'Aizawl',
    'Lunglei',
    'Champhai',
    'Kolasib'
  ],
  'Nagaland' => [
    'Kohima',
    'Dimapur',
    'Mokokchung',
    'Wokha'
  ],
  'Odisha' => [
    'Bhubaneswar',
    'Cuttack',
    'Rourkela',
    'Puri',
    'Sambalpur',
    'Berhampur',
    'Balasore'
  ],
  'Punjab' => [
    'Ludhiana',
    'Amritsar',
    'Jalandhar',
    'Patiala',
    'Mohali',
    'Bathinda',
    'Pathankot',
    'Hoshiarpur'
  ],
  'Rajasthan' => [
    'Jaipur',
    'Jodhpur',
    'Udaipur',
    'Kota',
    'Ajmer',
    'Bikaner',
    'Alwar',
    'Bhilwara',
    'Jaisalmer'
  ],
  'Sikkim' => [
    'Gangtok',
    'Namchi',
    'Gyalshing',
    'Mangan'
  ],
  'Tamil Nadu' => [
    'Chennai',
    'Coimbatore',
    'Madurai',
    'Tiruchirappalli',
    'Salem',
    'Tiruppur',
    'Vellore',
    'Erode',
    'Thanjavur',
    'Hosur'
  ],
  'Telangana' => [
    'Hyderabad',
    'Warangal',
    'Nizamabad',
    'Karimnagar',
    'Khammam',
    'Ramagundam',
    'Mahbubnagar'
  ],
  'Tripura' => [
    'Agartala',
    'Udaipur',
    'Dharmanagar',
    'Kailasahar'
  ],
  'Uttar Pradesh' => [
    'Lucknow',
    'Noida',
    'Greater Noida',
    'Ghaziabad',
    'Kanpur',
    'Varanasi',
    'Agra',
    'Prayagraj',
    'Meerut',
    'Gorakhpur',
    'Bareilly',
    'Mathura',
    'Ayodhya',
    'Aligarh'
  ],
  'Uttarakhand' => [
    'Dehradun',
    'Haridwar',
    'Rishikesh',
    'Haldwani',
    'Roorkee',
    'Nainital',
    'Rudrapur'
  ],
  'West Bengal' => [
    'Kolkata',
    'Howrah',
    'Siliguri',
    'Durgapur',
    'Asansol',
    'Darjeeling',
    'Kharagpur'
  ],
  'Andaman and Nicobar Islands' => [
    'Port Blair',
    'Diglipur',
    'Mayabunder'
  ],
  'Chandigarh' => [
    'Chandigarh'
  ],
  'Dadra and Nagar Haveli and Daman and Diu' => [
    'Daman',
    'Diu',
    'Silvassa'
  ],
  'Delhi' => [
    'New Delhi',
    'Delhi',
    'Dwarka',
    'Rohini',
    'Saket',
    'Shahdara'
  ],
  'Jammu and Kashmir' => [
    'Srinagar',
    'Jammu',
    'Anantnag',
    'Baramulla',
    'Gulmarg'
  ],
  'Ladakh' => [
    'Leh',
    'Kargil'
  ],
  'Lakshadweep' => [
    'Kavaratti',
    'Agatti',
    'Minicoy'
  ],
  'Puducherry' => [
    'Puducherry',
    'Karaikal',
    'Mahe',
    'Yanam'
  ]
};

        $now = now();
        $languages = DB::table('languages')->pluck('id');

        foreach ($languages as $languageId) {
            $country = DB::table('event_countries')
                ->where('language_id', $languageId)
                ->whereRaw('LOWER(name) = ?', ['india'])
                ->first();

            if (!$country) {
                $countryId = DB::table('event_countries')->insertGetId([
                    'language_id' => $languageId,
                    'name' => 'India',
                    'slug' => 'india',
                    'status' => 1,
                    'serial_number' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $countryId = $country->id;
                DB::table('event_countries')->where('id', $countryId)->update([
                    'status' => 1,
                    'updated_at' => $now,
                ]);
            }

            $stateSerial = 1;
            foreach ($locations as $stateName => $cities) {
                $state = DB::table('event_states')
                    ->where('language_id', $languageId)
                    ->where('country_id', $countryId)
                    ->whereRaw('LOWER(name) = ?', [Str::lower($stateName)])
                    ->first();

                if (!$state) {
                    $stateId = DB::table('event_states')->insertGetId([
                        'language_id' => $languageId,
                        'country_id' => $countryId,
                        'name' => $stateName,
                        'slug' => Str::slug($stateName),
                        'status' => 1,
                        'serial_number' => $stateSerial,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $stateId = $state->id;
                    DB::table('event_states')->where('id', $stateId)->update([
                        'country_id' => $countryId,
                        'status' => 1,
                        'updated_at' => $now,
                    ]);
                }

                $citySerial = 1;
                foreach ($cities as $cityName) {
                    $city = DB::table('event_cities')
                        ->where('language_id', $languageId)
                        ->where('country_id', $countryId)
                        ->where('state_id', $stateId)
                        ->whereRaw('LOWER(name) = ?', [Str::lower($cityName)])
                        ->first();

                    if (!$city) {
                        DB::table('event_cities')->insert([
                            'language_id' => $languageId,
                            'country_id' => $countryId,
                            'state_id' => $stateId,
                            'name' => $cityName,
                            'slug' => Str::slug($cityName),
                            'status' => 1,
                            'serial_number' => $citySerial,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    } else {
                        DB::table('event_cities')->where('id', $city->id)->update([
                            'country_id' => $countryId,
                            'state_id' => $stateId,
                            'status' => 1,
                            'updated_at' => $now,
                        ]);
                    }
                    $citySerial++;
                }
                $stateSerial++;
            }
        }
    }

    public function down()
    {
        // Reference data is intentionally retained on rollback to avoid deleting
        // locations that may already be referenced by events.
    }
};
