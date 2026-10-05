<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\Event;
use App\Models\Event\EventContent;
use App\Models\Event\Ticket;

class PrepareStaging extends Command
{
    protected $signature = 'booktkit:prepare-staging {--credentials= : Private JSON credential file} {--reset : Sanitize the staging clone and replace fixtures}';
    protected $description = 'Sanitize only the isolated staging database and seed disposable QA fixtures';

    public function handle(): int
    {
        if (!app()->environment('staging') || DB::connection()->getDatabaseName() !== 'booktkit_stage'
            || parse_url(config('app.url'), PHP_URL_HOST) !== 'test.booktkit.com') {
            $this->error('Refusing: exact staging environment, hostname and database required.');
            return 1;
        }
        $path = $this->option('credentials');
        if (!$this->option('reset') || !$path || !is_file($path)) {
            $this->error('Requires --reset and a private --credentials JSON file.');
            return 1;
        }
        $credentials = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        foreach (['CUSTOMER', 'ORGANIZER', 'ADMIN', 'SCANNER'] as $role) {
            if (strlen($credentials['BOOKTKIT_TEST_'.$role.'_PASSWORD'] ?? '') < 20) {
                $this->error('Missing strong dedicated test credentials.');
                return 1;
            }
        }
        $keep = ['migrations','basic_settings','languages','currencies','timezones','countries','states','cities',
            'event_countries','event_states','event_cities','event_categories','sections','section_titles',
            'page_headings','seos','mail_templates','online_gateways','footer_contents','menu_builders',
            'cookie_alerts','hero_sections','about_us_sections','how_works','how_work_items'];
        $gateway = DB::table('online_gateways')->where('keyword','razorpay')->first();
        $gatewayInfo = json_decode($gateway->information ?? '{}', true);
        if (!str_starts_with($gatewayInfo['key'] ?? '', 'rzp_test_')) {
            $this->error('Refusing clone: Razorpay TEST credentials required.');
            return 1;
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach (DB::select('SHOW TABLES') as $row) {
                $table = array_values((array)$row)[0];
                if (!in_array($table,$keep,true)) DB::table($table)->delete();
            }
            // Remove every integration secret except the explicitly checked Razorpay test gateway.
            DB::table('online_gateways')->where('keyword','!=','razorpay')->update(['status'=>0,'information'=>'{}']);
            $clear = [];
            foreach (Schema::getColumnListing('basic_settings') as $column) {
                if (preg_match('/secret|api_key|password|token|client_id|app_id|firebase|smtp_host|smtp_username/', $column)) $clear[$column] = null;
            }
            DB::table('basic_settings')->update($clear + [
                'website_title'=>'BookTKIT Staging','email_address'=>'qa@example.invalid','contact_number'=>null,
                'address'=>'Synthetic staging data','from_mail'=>'qa@example.invalid','to_mail'=>'qa@example.invalid',
                'from_name'=>'BookTKIT Staging','smtp_status'=>0,'google_recaptcha_status'=>0,
                'google_login_status'=>0,'facebook_login_status'=>0,'whatsapp_status'=>0,
                'google_map_status'=>0,'app_google_map_status'=>0,'ai_system_status'=>0,
                'organizer_email_verification'=>0,'organizer_admin_approval'=>0,'maintenance_status'=>0,
            ]);
            DB::table('support_ticket_statuses')->insert(['support_ticket_status'=>'active']);
            DB::table('earnings')->insert(['total_revenue'=>0,'total_earning'=>0]);
            $now = now();
            $customer = DB::table('customers')->insertGetId([
                'fname'=>'QA','lname'=>'Customer','username'=>'qa_customer',
                'email'=>$credentials['BOOKTKIT_TEST_CUSTOMER_EMAIL'],
                'password'=>Hash::make($credentials['BOOKTKIT_TEST_CUSTOMER_PASSWORD']),
                'status'=>1,'email_verified_at'=>$now,'created_at'=>$now,'updated_at'=>$now,
            ]);
            $organizer = DB::table('organizers')->insertGetId([
                'username'=>'qa_organizer','email'=>$credentials['BOOKTKIT_TEST_ORGANIZER_EMAIL'],
                'password'=>Hash::make($credentials['BOOKTKIT_TEST_ORGANIZER_PASSWORD']),
                'status'=>'1','amount'=>0,'theme_version'=>'light','email_verified_at'=>$now,
                'created_at'=>$now,'updated_at'=>$now,
            ]);
            foreach (['ADMIN','SCANNER'] as $role) DB::table('admins')->insert([
                'role_id'=>null,'first_name'=>'QA','last_name'=>ucfirst(strtolower($role)),
                'username'=>$credentials['BOOKTKIT_TEST_'.$role.'_USERNAME'],
                'email'=>'qa_'.strtolower($role).'@example.invalid',
                'password'=>Hash::make($credentials['BOOKTKIT_TEST_'.$role.'_PASSWORD']),
                'status'=>1,'created_at'=>$now,'updated_at'=>$now,
            ]);
            $languages=DB::table('languages')->get();
            foreach ($languages as $language) DB::table('organizer_infos')->insert([
                'organizer_id'=>$organizer,'language_id'=>$language->id,'name'=>'QA Organizer',
                'country'=>'India','state'=>'Maharashtra','city'=>'Jalgaon','address'=>'Synthetic QA venue',
            ]);
            $date=now()->addMonths(3)->format('Y-m-d');
            $fixtures=['customer_id'=>$customer,'organizer_id'=>$organizer,'events'=>[]];
            foreach (['venue','online'] as $type) {
                $event=Event::create(['organizer_id'=>$organizer,'thumbnail'=>'qa-fixture.png','status'=>'1',
                    'date_type'=>'single','start_date'=>$date,'end_date'=>$date,'start_time'=>'10:00','end_time'=>'12:00',
                    'end_date_time'=>$date.' 12:00:00','duration'=>'2h','event_type'=>$type,'is_featured'=>'no',
                    'meeting_url'=>$type==='online'?'https://example.invalid/qa-meeting':null]);
                foreach ($languages as $language) {
                    $category=DB::table('event_categories')->where('language_id',$language->id)->value('id');
                    if (!$category) $category=DB::table('event_categories')->value('id');
                    DB::table('event_contents')->insert(['event_id'=>$event->id,'language_id'=>$language->id,'event_category_id'=>$category,
                        'title'=>'QA Fixture '.ucfirst($type),'slug'=>'qa-fixture-'.$type,
                        'description'=>'<p>Synthetic BookTKIT event used only for isolated automated testing.</p>',
                        'address'=>$type==='venue'?'Synthetic QA venue, Jalgaon':null,
                        'country'=>'India','state'=>'Maharashtra','city'=>'Jalgaon','zip_code'=>'425001']);
                }
                $ticketIds=[];
                foreach (['free'=>0,'normal'=>100] as $pricing=>$price) {
                    $ticket=Ticket::create(['event_id'=>$event->id,'event_type'=>$type,'title'=>'QA '.$pricing,
                        'pricing_type'=>$pricing,'price'=>$price,'f_price'=>$price,'ticket_available_type'=>'limited',
                        'ticket_available'=>100,'max_ticket_buy_type'=>'limited','max_buy_ticket'=>4,
                        'early_bird_discount'=>'disable']);
                    foreach ($languages as $language) DB::table('ticket_contents')->insert([
                        'ticket_id'=>$ticket->id,'language_id'=>$language->id,'title'=>'QA '.$pricing,
                        'description'=>'Synthetic QA ticket','created_at'=>$now,'updated_at'=>$now]);
                    $ticketIds[$pricing]=$ticket->id;
                }
                $fixtures['events'][$type]=['id'=>$event->id,'slug'=>'qa-fixture-'.$type,'tickets'=>$ticketIds];
            }
            // Test fixture IDs only; never credentials. Kept outside the public directory.
            file_put_contents(storage_path('app/qa-fixtures.json'),json_encode($fixtures,JSON_PRETTY_PRINT));
            chmod(storage_path('app/qa-fixtures.json'),0600);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        $this->info('Staging sanitized: four dedicated roles, two events, free and paid ticket fixtures.');
        return 0;
    }
}
