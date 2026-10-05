<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Str;

return new class extends Migration
{
    public function up()
    {
        $locations = [
  'Andhra Pradesh' => [
    'Anantapur',
    'Eluru',
    'Ongole',
    'Srikakulam',
    'Vizianagaram',
    'Machilipatnam',
    'Chittoor',
    'Tenali',
    'Proddatur',
    'Nandyal',
    'Bhimavaram',
    'Hindupur',
    'Madanapalle',
    'Guntakal',
    'Dharmavaram',
    'Gudivada',
    'Narasaraopet',
    'Tadipatri'
  ],
  'Arunachal Pradesh' => [
    'Bomdila',
    'Tezu',
    'Roing',
    'Along',
    'Namsai',
    'Khonsa'
  ],
  'Assam' => [
    'Sivasagar',
    'Bongaigaon',
    'North Lakhimpur',
    'Dhubri',
    'Diphu',
    'Goalpara',
    'Karimganj',
    'Hailakandi',
    'Golaghat',
    'Barpeta'
  ],
  'Bihar' => [
    'Ara',
    'Begusarai',
    'Katihar',
    'Chhapra',
    'Hajipur',
    'Munger',
    'Bettiah',
    'Motihari',
    'Samastipur',
    'Sasaram',
    'Sitamarhi',
    'Madhubani',
    'Siwan',
    'Saharsa',
    'Kishanganj',
    'Jamalpur'
  ],
  'Chhattisgarh' => [
    'Rajnandgaon',
    'Ambikapur',
    'Dhamtari',
    'Mahasamund',
    'Jagdalpur',
    'Kanker',
    'Janjgir',
    'Chirmiri'
  ],
  'Goa' => [
    'Calangute',
    'Candolim',
    'Bicholim',
    'Cuncolim',
    'Quepem',
    'Sanquelim'
  ],
  'Gujarat' => [
    'Bharuch',
    'Mehsana',
    'Morbi',
    'Vapi',
    'Navsari',
    'Porbandar',
    'Bhuj',
    'Gandhidham',
    'Nadiad',
    'Palanpur',
    'Godhra',
    'Patan',
    'Dahod',
    'Ankleshwar',
    'Valsad',
    'Surendranagar'
  ],
  'Haryana' => [
    'Yamunanagar',
    'Kurukshetra',
    'Sirsa',
    'Bhiwani',
    'Rewari',
    'Bahadurgarh',
    'Jind',
    'Kaithal',
    'Palwal',
    'Fatehabad',
    'Narnaul'
  ],
  'Himachal Pradesh' => [
    'Hamirpur',
    'Bilaspur',
    'Dalhousie',
    'Chamba',
    'Nahan',
    'Palampur',
    'Kangra',
    'Kasauli'
  ],
  'Jharkhand' => [
    'Giridih',
    'Ramgarh',
    'Dumka',
    'Medininagar',
    'Chaibasa',
    'Phusro',
    'Adityapur',
    'Sahibganj'
  ],
  'Karnataka' => [
    'Kalaburagi',
    'Tumakuru',
    'Hassan',
    'Vijayapura',
    'Bidar',
    'Raichur',
    'Kolar',
    'Chikkamagaluru',
    'Mandya',
    'Gadag',
    'Bagalkot',
    'Hospet',
    'Karwar'
  ],
  'Kerala' => [
    'Malappuram',
    'Kasaragod',
    'Pathanamthitta',
    'Thalassery',
    'Ponnani',
    'Vatakara',
    'Guruvayur',
    'Muvattupuzha'
  ],
  'Madhya Pradesh' => [
    'Dewas',
    'Ratlam',
    'Singrauli',
    'Chhindwara',
    'Khandwa',
    'Burhanpur',
    'Shivpuri',
    'Vidisha',
    'Morena',
    'Neemuch',
    'Mandsaur',
    'Itarsi',
    'Katni'
  ],
  'Maharashtra' => [
    'Sangli',
    'Jalgaon',
    'Akola',
    'Latur',
    'Ahilyanagar',
    'Satara',
    'Ratnagiri',
    'Chandrapur',
    'Parbhani',
    'Dhule',
    'Jalna',
    'Beed',
    'Wardha',
    'Yavatmal',
    'Bhiwandi',
    'Malegaon',
    'Panvel'
  ],
  'Manipur' => [
    'Ukhrul',
    'Kakching',
    'Senapati',
    'Moreh'
  ],
  'Meghalaya' => [
    'Sohra',
    'Williamnagar',
    'Baghmara',
    'Nongstoin'
  ],
  'Mizoram' => [
    'Serchhip',
    'Saiha',
    'Lawngtlai',
    'Mamit'
  ],
  'Nagaland' => [
    'Tuensang',
    'Zunheboto',
    'Mon',
    'Phek'
  ],
  'Odisha' => [
    'Jharsuguda',
    'Baripada',
    'Angul',
    'Balangir',
    'Jeypore',
    'Bhadrak',
    'Rayagada',
    'Dhenkanal',
    'Keonjhar',
    'Paradip'
  ],
  'Punjab' => [
    'Moga',
    'Firozpur',
    'Batala',
    'Abohar',
    'Khanna',
    'Phagwara',
    'Barnala',
    'Kapurthala',
    'Malerkotla',
    'Sangrur',
    'Rupnagar'
  ],
  'Rajasthan' => [
    'Bharatpur',
    'Sikar',
    'Sri Ganganagar',
    'Mount Abu',
    'Pali',
    'Chittorgarh',
    'Jhunjhunu',
    'Tonk',
    'Bundi',
    'Barmer',
    'Hanumangarh',
    'Beawar',
    'Kishangarh'
  ],
  'Sikkim' => [
    'Singtam',
    'Rangpo',
    'Jorethang',
    'Ravangla'
  ],
  'Tamil Nadu' => [
    'Tirunelveli',
    'Nagercoil',
    'Dindigul',
    'Kanchipuram',
    'Thoothukudi',
    'Cuddalore',
    'Karur',
    'Kumbakonam',
    'Sivakasi',
    'Pollachi',
    'Nagapattinam',
    'Ramanathapuram',
    'Krishnagiri',
    'Namakkal',
    'Ooty'
  ],
  'Telangana' => [
    'Nalgonda',
    'Siddipet',
    'Adilabad',
    'Suryapet',
    'Miryalaguda',
    'Jagtial',
    'Mancherial',
    'Nirmal',
    'Kamareddy',
    'Medak'
  ],
  'Tripura' => [
    'Belonia',
    'Khowai',
    'Ambassa',
    'Teliamura'
  ],
  'Uttar Pradesh' => [
    'Jhansi',
    'Saharanpur',
    'Muzaffarnagar',
    'Firozabad',
    'Moradabad',
    'Gonda',
    'Faizabad',
    'Rampur',
    'Shahjahanpur',
    'Etawah',
    'Mirzapur',
    'Bulandshahr',
    'Raebareli',
    'Sitapur',
    'Bahraich',
    'Basti',
    'Azamgarh',
    'Jaunpur',
    'Sultanpur',
    'Hapur'
  ],
  'Uttarakhand' => [
    'Kashipur',
    'Mussoorie',
    'Ramnagar',
    'Kotdwar',
    'Pithoragarh',
    'Almora',
    'Srinagar Garhwal',
    'Ranikhet'
  ],
  'West Bengal' => [
    'Haldia',
    'Bardhaman',
    'Malda',
    'Kalyani',
    'Krishnanagar',
    'Jalpaiguri',
    'Cooch Behar',
    'Bankura',
    'Purulia',
    'Raiganj',
    'Balurghat',
    'Serampore',
    'Chandannagar'
  ],
  'Andaman and Nicobar Islands' => [
    'Rangat',
    'Havelock Island'
  ],
  'Dadra and Nagar Haveli and Daman and Diu' => [
    'Amli'
  ],
  'Delhi' => [
    'Narela'
  ],
  'Jammu and Kashmir' => [
    'Udhampur',
    'Kathua',
    'Sopore',
    'Pulwama',
    'Kupwara',
    'Pahalgam'
  ],
  'Ladakh' => [
    'Diskit'
  ],
  'Lakshadweep' => [
    'Andrott'
  ],
  'Puducherry' => [
    'Oulgaret'
  ]
];

        $now = now();
        foreach (DB::table('languages')->pluck('id') as $languageId) {
            $country = DB::table('event_countries')->where('language_id', $languageId)->whereRaw('LOWER(name) = ?', ['india'])->first();
            if (!$country) { continue; }
            foreach ($locations as $stateName => $cities) {
                $state = DB::table('event_states')->where('language_id', $languageId)->where('country_id', $country->id)->whereRaw('LOWER(name) = ?', [Str::lower($stateName)])->first();
                if (!$state) { continue; }
                $serial = (int) DB::table('event_cities')->where('state_id', $state->id)->max('serial_number');
                foreach ($cities as $cityName) {
                    $existing = DB::table('event_cities')->where('language_id', $languageId)->where('country_id', $country->id)->where('state_id', $state->id)->whereRaw('LOWER(name) = ?', [Str::lower($cityName)])->first();
                    if (!$existing) {
                        DB::table('event_cities')->insert(['language_id'=>$languageId,'country_id'=>$country->id,'state_id'=>$state->id,'name'=>$cityName,'slug'=>Str::slug($cityName),'status'=>1,'serial_number'=>++$serial,'created_at'=>$now,'updated_at'=>$now]);
                    }
                }
            }
        }
    }

    public function down() { /* Retain reference data to protect event relationships. */ }
};
