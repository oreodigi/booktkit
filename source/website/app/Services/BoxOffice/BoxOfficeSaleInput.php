<?php

namespace App\Services\BoxOffice;

use App\Models\BoxOfficeSetting;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * One validation contract for organizer and staff POS sales, driven by POS settings
 * (platform default <- organizer <- event). Identity data is encrypted/stored privately here.
 */
class BoxOfficeSaleInput
{
    public const DEFAULTS = ['hold_minutes' => 10, 'allow_cash' => true, 'allow_upi' => true, 'allow_card' => true, 'allow_other' => true,
        'require_customer_name' => true, 'require_customer_phone' => true, 'allow_aadhaar' => false, 'allow_customer_photo' => true,
        'auto_email_ticket' => false, 'receipt_width_mm' => 80];

    public static function settings(int $organizerId, ?int $eventId = null): array
    {
        $rows = BoxOfficeSetting::where(function ($q) use ($organizerId, $eventId) {
            $q->where(fn ($x) => $x->whereNull('organizer_id')->whereNull('event_id'))
              ->orWhere(fn ($x) => $x->where('organizer_id', $organizerId)->whereNull('event_id'));
            if ($eventId) $q->orWhere(fn ($x) => $x->where('organizer_id', $organizerId)->where('event_id', $eventId));
        })->get()->sortBy(fn ($r) => ($r->organizer_id ? 1 : 0) + ($r->event_id ? 1 : 0));
        $settings = self::DEFAULTS;
        foreach ($rows as $row) {
            foreach (array_keys(self::DEFAULTS) as $key) if ($row->{$key} !== null) $settings[$key] = $row->{$key};
        }
        return $settings;
    }

    public static function allowedMethods(array $settings): array
    {
        $methods = array_values(array_filter(['cash', 'upi', 'card', 'other'], fn ($m) => (bool) ($settings['allow_' . $m] ?? false)));
        return $methods ?: ['cash', 'upi', 'card', 'other'];
    }

    public function validate(Request $r, int $organizerId): array
    {
        $eventId = (int) $r->input('event_id');
        $settings = self::settings($organizerId, $eventId ?: null);

        $d = $r->validate([
            'sale_uuid' => 'required|uuid', 'event_id' => 'required|integer', 'location_id' => 'required|integer',
            'customer_name' => [$settings['require_customer_name'] ? 'required' : 'nullable', 'string', 'max:120'],
            'customer_phone' => [$settings['require_customer_phone'] ? 'required' : 'nullable', 'string', 'max:30'],
            'customer_email' => ['required', 'email', 'max:190'],
            'customer_age' => 'nullable|integer|min:1|max:120',
            'aadhaar_number' => $settings['allow_aadhaar'] ? 'nullable|digits:12' : 'prohibited',
            'aadhaar_document' => $settings['allow_aadhaar'] ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120' : 'prohibited',
            'customer_photo' => $settings['allow_customer_photo'] ? 'nullable|image|mimes:jpg,jpeg,png|max:5120' : 'prohibited',
            'event_date' => 'nullable|string|max:80', 'deliver_email' => 'nullable|boolean',
            'payment_method' => ['required', Rule::in(self::allowedMethods($settings))],
            'payment_reference' => 'nullable|string|max:120',
            'items' => 'required|array|min:1', 'items.*.ticket_id' => 'required|integer', 'items.*.quantity' => 'required|integer|min:1|max:50',
            'items.*.variation' => 'nullable|string', 'items.*.pass_product_id' => 'nullable|integer',
            'items.*.event_date_ids' => 'nullable|array', 'items.*.event_date_ids.*' => 'integer', 'cash_received' => 'nullable|numeric|min:0',
        ], ['payment_method.in' => 'This payment method is turned off in POS settings.', '*.prohibited' => 'Identity capture is turned off in POS settings.']);

        $this->assertEventDate($d);
        $d['customer_name'] = $d['customer_name'] ?? 'Walk-in customer';
        $d['customer_phone'] = $d['customer_phone'] ?? '';
        if ((bool) $settings['auto_email_ticket'] && !empty($d['customer_email'])) $d['deliver_email'] = true;

        if (!empty($d['aadhaar_number'])) {
            $d['aadhaar_number_encrypted'] = Crypt::encryptString($d['aadhaar_number']);
            $d['aadhaar_last4'] = substr($d['aadhaar_number'], -4);
        }
        unset($d['aadhaar_number']);
        if ($r->hasFile('aadhaar_document')) $d['aadhaar_document_path'] = $r->file('aadhaar_document')->store('box-office/identity', 'local');
        if ($r->hasFile('customer_photo')) $d['customer_photo_path'] = $r->file('customer_photo')->store('box-office/customers', 'local');
        return $d;
    }

    /** Multi-date events need a valid session for ticket lines (passes carry their own dates). */
    private function assertEventDate(array $d): void
    {
        $event = Event::find($d['event_id']);
        if (!$event || $event->date_type !== 'multiple') return;
        $needsDate = collect($d['items'])->contains(fn ($i) => empty($i['pass_product_id']));
        $date = trim((string) ($d['event_date'] ?? ''));
        if ($date === '') {
            if ($needsDate) throw ValidationException::withMessages(['event_date' => 'Select the event date for these tickets.']);
            return;
        }
        $valid = DB::table('event_dates')->where('event_id', $event->id)->pluck('start_date')->map(fn ($x) => substr((string) $x, 0, 10))->all();
        if (!in_array(substr($date, 0, 10), $valid, true)) throw ValidationException::withMessages(['event_date' => 'This date is not part of the event.']);
    }
}
