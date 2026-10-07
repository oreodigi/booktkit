<?php

namespace Tests\Feature\Hardening;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Event\Booking;
use App\Models\Event\Ticket;
use App\Models\Organizer;
use App\Models\OrganizerStaff;
use Illuminate\Support\Facades\DB;

/** Minimal BookTKIT data for HTTP-level regression tests (booktkit_test only). */
trait BuildsFixtures
{
    protected function seedPlatform(): void
    {
        if (!DB::table('languages')->where('is_default', 1)->exists()) {
            DB::table('languages')->insert(['name' => 'English', 'code' => 'en', 'direction' => 0, 'is_default' => 1]);
        }
        if (!DB::table('basic_settings')->exists()) {
            DB::table('basic_settings')->insert(['uniqid' => 12345, 'theme_version' => 1, 'website_title' => 'BookTKIT QA', 'base_currency_text' => 'INR',
                'base_currency_symbol' => '₹', 'base_currency_rate' => 1, 'tax' => 0, 'commission' => 0, 'event_guest_checkout_status' => 1]);
        }
        if (!DB::table('admins')->exists()) {
            DB::table('admins')->insert(['first_name' => 'QA', 'last_name' => 'Admin', 'username' => 'qa-admin', 'email' => 'qa-admin@example.invalid', 'password' => bcrypt('secret-pass'), 'status' => 1]);
        }
        if (!DB::table('currencies')->where('is_default', 1)->exists()) {
            DB::table('currencies')->insert(['text' => 'INR', 'symbol' => '₹', 'text_position' => 'right', 'symbol_position' => 'left', 'value' => 1, 'is_default' => 1]);
        }
        // AppServiceProvider shares this only for web requests (not console/test runs); error pages need it.
        \Illuminate\Support\Facades\View::share('websiteInfo', \App\Models\BasicSettings\Basic::first());
        if (!DB::table('online_gateways')->where('keyword', 'razorpay')->exists()) {
            DB::table('online_gateways')->insert(['name' => 'Razorpay', 'keyword' => 'razorpay', 'information' => json_encode(['key' => 'rzp_test_x', 'secret' => 'secret']), 'status' => 1]);
        }
    }

    protected function organizer(): Organizer
    {
        return Organizer::create(['username' => uniqid('org'), 'email' => uniqid('org') . '@example.invalid', 'password' => bcrypt('secret-pass'), 'status' => 1, 'email_verified_at' => now()]);
    }

    protected function admin(): Admin
    {
        $this->seedPlatform();
        return Admin::firstOrFail();
    }

    protected function customer(): Customer
    {
        return Customer::create(['fname' => 'Qa', 'email' => uniqid('cus') . '@example.invalid', 'password' => bcrypt('secret-pass'), 'status' => 1, 'email_verified_at' => now()]);
    }

    protected function event(Organizer $organizer, array $overrides = []): Event
    {
        $event = Event::create(array_merge([
            'organizer_id' => $organizer->id, 'thumbnail' => 'qa.jpg', 'status' => '1', 'event_type' => 'venue', 'date_type' => 'single',
            'start_date' => now()->toDateString(), 'start_time' => '00:00', 'end_date' => now()->addDays(2)->toDateString(), 'end_time' => '23:00',
            'end_date_time' => now()->addDays(2)->setTime(23, 0)->toDateTimeString(), 'is_featured' => 'no',
        ], $overrides));
        DB::table('event_contents')->insert(['event_id' => $event->id, 'language_id' => DB::table('languages')->value('id') ?? 1, 'event_category_id' => 1,
            'title' => 'QA Event ' . $event->id, 'slug' => 'qa-event-' . $event->id, 'address' => 'Somewhere', 'city' => 'Pune']);
        return $event;
    }

    protected function ticket(Event $event, array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'event_id' => $event->id, 'event_type' => $event->event_type, 'title' => 'General', 'pricing_type' => 'normal', 'price' => 500, 'f_price' => 500,
            'ticket_available_type' => 'limited', 'ticket_available' => 100, 'max_ticket_buy_type' => 'unlimited', 'early_bird_discount' => 'disable',
            'admission_pass_type' => 'mobile_qr', 'reentry_policy' => 'none',
        ], $overrides));
    }

    protected function booking(Event $event, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'customer_id' => 'guest', 'booking_id' => uniqid('bk'), 'event_id' => $event->id, 'organizer_id' => $event->organizer_id,
            'fname' => 'Qa', 'lname' => 'Buyer', 'email' => 'buyer@example.invalid', 'phone' => '9999999999', 'country' => 'India', 'address' => 'x',
            'variation' => json_encode([]), 'price' => 500, 'quantity' => 1, 'tax' => 0, 'commission' => 0, 'discount' => 0, 'early_bird_discount' => 0,
            'paymentMethod' => 'Razorpay', 'gatewayType' => 'online', 'paymentStatus' => 'completed', 'event_date' => now()->toDateString(),
        ], $overrides));
    }

    protected function staff(Organizer $organizer, array $permissions, ?Event $assignedTo = null, array $overrides = []): OrganizerStaff
    {
        $staff = OrganizerStaff::create(array_merge([
            'organizer_id' => $organizer->id, 'name' => 'QA Staff', 'username' => uniqid('staff'), 'password' => bcrypt('secret-pass'),
            'role' => 'event_manager', 'department' => 'operations', 'permissions' => $permissions, 'active' => true, 'must_change_password' => false,
        ], $overrides));
        if ($assignedTo) $staff->assignments()->create(['event_id' => $assignedTo->id]);
        return $staff;
    }

    /** Sign in as staff the way the team login does: staff guard plus the organizer guard. */
    protected function actingAsStaff(OrganizerStaff $staff): self
    {
        return $this->actingAs($staff, 'staff')->actingAs($staff->organizer, 'organizer');
    }
}
