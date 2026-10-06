<?php

namespace App\Http\Requests\Event;

use App\Models\Event;
use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventFormRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $routeType = $this->query('type');
        if (!$this->input('event_id') && in_array($routeType, ['venue', 'online', 'box_office'], true)) {
            $this->merge(['event_type' => $routeType]);
        }
    }

    public function rules(): array
    {
        $eventId = (int) ($this->input('event_id') ?: 0);
        $editing = $eventId > 0;
        $rules = [
            'event_id' => ['nullable','integer','exists:events,id'],
            'event_type' => ['required', Rule::in(['venue','online','box_office'])],
            'box_office_enabled' => ['nullable','boolean'],
            'reentry_policy' => ['required_if:box_office_enabled,1', Rule::in(['none','unlimited','limited'])],
            'max_reentries' => ['exclude_unless:reentry_policy,limited','required_if:reentry_policy,limited','integer','min:1'],
            'box_office_locations' => ['nullable','required_if:box_office_enabled,1','array','min:1'],
            'box_office_locations.*.name' => ['required_with:box_office_locations','string','max:255'],
            'box_office_locations.*.address' => ['required_with:box_office_locations','string','max:500'],
            'box_office_locations.*.active' => ['nullable','boolean'],
            'date_type' => ['required', Rule::in(['single','multiple'])],
            'status' => ['required'],
            'is_featured' => ['required'],
            'thumbnail' => [$editing ? 'nullable' : 'required_without:thumbnail_image_url','image','mimes:jpg,jpeg,png','max:1024','dimensions:width=320,height=230'],
            'thumbnail_image_url' => ['nullable','string'],
            'slider_images' => [$editing ? 'nullable' : 'required','array'],
            'slider_images.*' => ['integer','exists:event_images,id'],
            'start_date' => ['required_if:date_type,single','nullable','date'],
            'start_time' => ['required_if:date_type,single','nullable'],
            'end_date' => ['required_if:date_type,single','nullable','date','after_or_equal:start_date'],
            'end_time' => ['required_if:date_type,single','nullable'],
            'm_start_date' => ['required_if:date_type,multiple','array','min:1'],
            'm_start_date.*' => ['required','date'],
            'm_start_time.*' => ['required'],
            'm_end_date.*' => ['required','date'],
            'm_end_time.*' => ['required'],
            'date_ids' => ['nullable','array'],
            'date_ids.*' => ['nullable','integer','exists:event_dates,id'],
            'meeting_url' => ['required_if:event_type,online','nullable','url','max:2048'],
            'ticket_available_type' => ['required_if:event_type,online','nullable'],
            'ticket_available' => ['nullable','integer','min:1'],
            'max_ticket_buy_type' => ['required_if:event_type,online','nullable'],
            'max_buy_ticket' => ['nullable','integer','min:1'],
            'price' => ['nullable','numeric','min:0'],
            'pricing_type' => ['nullable'],
            'latitude' => ['nullable','numeric','between:-90,90'],
            'longitude' => ['nullable','numeric','between:-180,180'],
        ];

        if (auth('admin')->check()) $rules['organizer_id'] = ['nullable','integer','exists:organizers,id'];
        else $rules['organizer_id'] = ['prohibited'];

        foreach (Language::all() as $language) {
            $code=$language->code;
            $rules[$code.'_title']=['required','string','max:255'];
            $rules[$code.'_category_id']=['required','integer','exists:event_categories,id'];
            $rules[$code.'_description']=['required','string','min:30','max:1200'];
            $rules[$code.'_address']=[Rule::requiredIf(fn () => in_array($this->input('event_type'), ['venue','box_office'], true)),'nullable','string','max:500'];
            $rules[$code.'_country']=['nullable','integer'];
            $rules[$code.'_state']=['nullable','integer'];
            $rules[$code.'_city']=[Rule::requiredIf(fn () => in_array($this->input('event_type'), ['venue','box_office'], true)),'nullable','integer'];
            $rules[$code.'_zip_code']=['nullable','string','max:30'];
            $rules[$code.'_refund_policy']=['nullable','string'];
            $rules[$code.'_meta_keywords']=['nullable','string'];
            $rules[$code.'_meta_description']=['nullable','string'];
        }
        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('date_type') === 'single' && $this->filled(['start_date','start_time','end_date','end_time'])) {
                $start=strtotime($this->start_date.' '.$this->start_time); $end=strtotime($this->end_date.' '.$this->end_time);
                if ($end <= $start) $validator->errors()->add('end_time','Event end must be after the start.');
            }
            if ($this->input('date_type') === 'multiple') {
                foreach (($this->input('m_start_date') ?: []) as $i=>$date) {
                    $start=strtotime($date.' '.($this->input("m_start_time.$i") ?: ''));
                    $end=strtotime(($this->input("m_end_date.$i") ?: '').' '.($this->input("m_end_time.$i") ?: ''));
                    if ($start && $end && $end <= $start) $validator->errors()->add("m_end_time.$i",'Event end must be after the start.');
                }
            }
            
            $eventId=(int)$this->input('event_id');
            if ($eventId && $this->input('event_type') && ($event=Event::find($eventId)) && $event->event_type !== $this->input('event_type') && $event->booking()->exists()) {
                $validator->errors()->add('event_type','Event type cannot be changed after bookings exist.');
            }
        });
    }
}
