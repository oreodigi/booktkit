<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Api\HelperController;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\Language;
use App\Models\Event\Ticket;
use Illuminate\Http\Request;
use App\Models\Event\EventCity;
use App\Models\Event\EventDates;
use App\Models\Event\EventImage;
use App\Models\Event\EventState;
use App\Models\Event\EventContent;
use App\Models\Event\EventCountry;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\TicketSettingRequest;
use App\Models\Event\EventCategory;
use App\Rules\ImageMimeTypeRule;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
  //index
  public function index(Request $request)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $information['langs'] = Language::all();

    //get language
    $language = HelperController::getAppLanguage($request);
    $information['language'] = $language;

    $event_type = request()->input('event_type');
    $title = null;
    if (request()->filled('title')) {
      $title = request()->input('title');
    }

    $events = Event::join('event_contents', 'event_contents.event_id', '=', 'events.id')
      ->join('event_categories', 'event_categories.id', '=', 'event_contents.event_category_id')
      ->where('event_contents.language_id', '=', $language->id)
      ->when($title, function ($query) use ($title) {
        return $query->where('event_contents.title', 'like', '%' . $title . '%');
      })
      ->where('events.organizer_id', '=', $organizer_id)
      ->when($event_type, function ($query, $event_type) {
        return $query->where('events.event_type', $event_type);
      })
      ->select('event_contents.id as eventInfoId','events.id as eventId',  'events.status', 'events.event_type', 'events.is_featured',  'event_contents.title', 'event_contents.slug', 'event_categories.name as category')
      ->orderByDesc('events.id')
      ->paginate(10);

    $information['events'] = $events;

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  //online_event
  public function addEvent()
  {
    // get all the languages from db
    $languages = Language::get();
    $information['getCurrencyInfo']  = $this->getCurrencyInfo();
    $information['languages'] = $languages;
    $information['basic_settings'] = DB::table('basic_settings')
      ->select('event_country_status', 'event_state_status', 'event_guest_checkout_status')
      ->first();

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  //all categories
  public function allCategories($id)
  {
    $categories = EventCategory::where('language_id', $id)
      ->orderBy('serial_number', 'asc')
      ->select('id', 'name', 'slug')
      ->get();

    $information['categories'] = $categories;
    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  //all countries
  public function allCountries($language_id)
  {
    $countries = EventCountry::where('language_id', $language_id)
      ->orderBy('serial_number', 'asc')
      ->select('id', 'name', 'slug')
      ->get();
    $information['countries'] = $countries;
    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  //all states
  public function allStates($language_id)
  {
    $states = EventState::where('language_id', $language_id)
      ->orderBy('serial_number', 'asc')
      ->select('id', 'name', 'slug')
      ->get();

    $information['states'] = $states;
    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }
  //all states
  public function allCities($language_id)
  {
    $cities = EventCity::where('language_id', $language_id)
      ->orderBy('serial_number', 'asc')
      ->select('id', 'name', 'slug')
      ->get();

    $information['cities'] = $cities;
    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  //city_state
  public function city_state($country_id)
  {
    $city = EventCity::where('country_id', $country_id)->orderBy('serial_number', 'asc')
      ->select('id', 'name', 'slug')
      ->get();
    $state = EventState::where('country_id', $country_id)->orderBy('serial_number', 'asc')
      ->select('id', 'name', 'slug')
      ->get();

    $information['cities'] = $city;
    $information['states'] = $state;

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  //city_state
  public function city($state_id)
  {
    $city = EventCity::where('state_id', $state_id)->orderBy('serial_number', 'asc')
      ->select('id', 'name', 'slug')
      ->get();

    $information['cities'] = $city;

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }



  public function gallerystore(Request $request)
  {
    $rules = [
      'file' => 'required|image|mimes:jpg,jpeg,png'
    ];
    $messages = [
      'file.required' => 'Please upload an image file.',
      'file.image'    => 'The uploaded file must be an image.',
      'file.mimes'    => 'Only jpg, jpeg, and png files are allowed.'
    ];

    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      $validator->getMessageBag()->add('error', 'true');
      return response()->json($validator->errors(), 422);
    }

    $img = $request->file('file');
    list($width, $height) = getimagesize($img->getPathname());

    if ($width != 1170 || $height != 570) {
      return response()->json([
        'status'  => 'error',
        'msg' => 'The image dimensions must be exactly 1170x570 pixels.'
      ]);
    }

    $filename = uniqid() . '.jpg';
    $uploadPath = public_path('assets/admin/img/event-gallery/');
    if (!file_exists($uploadPath)) {
      @mkdir($uploadPath, 0775, true);
    }
    $img->move($uploadPath, $filename);

    $pi = new EventImage;
    $pi->event_id = $request->event_id ?? null;
    $pi->image = $filename;
    $pi->save();

    return response()->json([
      'status'  => 'success',
      'file_id' => $pi->id
    ]);
  }
  public function imagermv(Request $request)
  {
    $pi = EventImage::where('id', $request->fileid)->first();
    @unlink(public_path('assets/admin/img/event-gallery/') . $pi->image);
    $pi->delete();
    return $pi->id;
  }

  public function store(Request $request)
  {

    $rules = [
      'event_type' => 'required',
      'date_type' => 'required',
      'countdown_status' => 'required',
      'slider_images'     => 'required|array',
      'slider_images.*' => [
        'required',
        'image',
        'mimes:jpg,jpeg,png',
        function ($attribute, $value, $fail) {
          if ($value && is_file($value->getPathname())) {
            [$width, $height] = getimagesize($value->getPathname());

            if ($width != 1170 || $height != 570) {
              $fail('Each slider image must be exactly 1170x570 pixels.');
            }
          }
        },
      ],

      'thumbnail' => [
        'required',
        new ImageMimeTypeRule(),
        function ($attribute, $value, $fail) {
          if ($value && is_file($value->getPathname())) {
            [$width, $height] = getimagesize($value->getPathname());
            if ($width != 320 || $height != 230) {
              $fail(__('The thumbnail image dimensions must be exactly 320x230 pixels.'));
            }
          }
        }
      ],
      'status'      => 'required',
      'is_featured' => 'required',
    ];

    /* -------------------- DATE TYPE -------------------- */
    if ($request->date_type === 'single') {
      $rules['start_date'] = 'required';
      $rules['start_time'] = 'required';
      $rules['end_date']   = 'required';
      $rules['end_time']   = 'required';
    }
    if ($request->date_type === 'multiple') {
      $rules['m_start_date']   = 'required|array';
      $rules['m_start_date.*'] = 'required|date';

      $rules['m_start_time']   = 'required|array';
      $rules['m_start_time.*'] = 'required';

      $rules['m_end_date']     = 'required|array';
      $rules['m_end_date.*']   = 'required|date';

      $rules['m_end_time']     = 'required|array';
      $rules['m_end_time.*']   = 'required';
    }


    /* -------------------- EVENT TYPE : ONLINE -------------------- */
    if ($request->event_type === 'online') {

      $rules['early_bird_discount_type'] = 'required';
      $rules['meeting_url'] = 'required|url';
      $rules['discount_type'] = 'required_if:early_bird_discount_type,enable';
      $rules['early_bird_discount_amount'] = 'required_if:early_bird_discount_type,enable';
      $rules['early_bird_discount_date'] = 'required_if:early_bird_discount_type,enable';
      $rules['early_bird_discount_time'] = 'required_if:early_bird_discount_type,enable';
      $rules['ticket_available_type'] = 'required';
      $rules['max_ticket_buy_type'] = 'required';

      if ($request->filled('ticket_available_type') && $request->ticket_available_type === 'limited') {
        $rules['ticket_available'] = 'required';
      }

      if ($request->filled('max_ticket_buy_type') && $request->max_ticket_buy_type === 'limited') {
        $rules['max_buy_ticket'] = 'required';
      }

      if (!$request->filled('pricing_type')) {
        $rules['price'] = 'required';
      }

      if (
        $request->early_bird_discount_type === 'enable' &&
        $request->discount_type === 'percentage'
      ) {
        $rules['early_bird_discount_amount'] = 'numeric|between:1,99';
      }

      if (
        $request->early_bird_discount_type === 'enable' &&
        $request->discount_type === 'fixed'
      ) {
        $price = max(($request->price ?? 1) - 1, 1);
        $rules['early_bird_discount_amount'] = "numeric|between:1,$price";
      }
    }

    /* -------------------- EVENT TYPE : VENUE -------------------- */
    if ($request->event_type === 'venue') {
      $rules['latitude']  = 'required_if:event_type,venue';
      $rules['longitude'] = 'required_if:event_type,venue';
    }

    /* -------------------- BASIC SETTINGS -------------------- */
    $bs = DB::table('basic_settings')
      ->select('event_country_status', 'event_state_status')
      ->first();

    /* -------------------- LANGUAGE BASED RULES -------------------- */
    $languages = Language::all();

    foreach ($languages as $language) {

      $titleField = $language->code . '_title';
      $slug = createSlug($request->$titleField);

      $rules[$titleField] = [
        'required',
        'max:255',
        function ($attribute, $value, $fail) use ($slug, $language) {
          $exists = EventContent::where('language_id', $language->id)
            ->whereRaw('LOWER(slug) = ?', [strtolower($slug)])
            ->exists();

          if ($exists) {
            $fail(__('The title field must be unique for :lang language.', [
              'lang' => $language->name
            ]));
          }
        }
      ];

      if ($bs->event_country_status == 1) {
        $rules[$language->code . '_country'] = 'required_if:event_type,venue';
      }

      $totalState = EventState::where('language_id', $language->id)->count();

      if ($bs->event_state_status == 1 && $totalState > 0) {
        $rules[$language->code . '_state'] = 'required_if:event_type,venue';
      }

      $rules[$language->code . '_category_id'] = 'required';
      $rules[$language->code . '_description'] = 'min:30';
      $rules[$language->code . '_address'] = 'required_if:event_type,venue';
      $rules[$language->code . '_city'] = 'required_if:event_type,venue';
    }

    /* -------------------- VALIDATOR -------------------- */
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors'  => $validator->errors()->toArray()
      ], 400);
    }

    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    //calculate duration
    if ($request->date_type == 'single') {
      $start = Carbon::parse($request->start_date . $request->start_time);
      $end =  Carbon::parse($request->end_date . $request->end_time);
      $diffent = DurationCalulate($start, $end);
    }
    //calculate duration end

    $in = $request->all();
    $in['duration'] = $request->date_type == 'single' ? $diffent : '';

    $img = $request->file('thumbnail');

    $in['organizer_id'] = $organizer_id;
    if ($request->hasFile('thumbnail')) {
      $filename = time() . '.' . $img->getClientOriginalExtension();
      $directory = public_path('assets/admin/img/event/thumbnail/');
      @mkdir($directory, 0775, true);
      $request->file('thumbnail')->move($directory, $filename);
      $in['thumbnail'] = $filename;
    }
    $in['f_price'] = $request->price;
    $in['end_date_time'] = Carbon::parse($request->end_date . ' ' . $request->end_time);
    $event = Event::create($in);

    if ($request->date_type == 'multiple') {
      $i = 1;
      foreach ($request->m_start_date as $key => $date) {
        $start = Carbon::parse($date . $request->m_start_time[$key]);
        $end =  Carbon::parse($request->m_end_date[$key] . $request->m_end_time[$key]);
        $diffent = DurationCalulate($start, $end);

        EventDates::create([
          'event_id' => $event->id,
          'start_date' => $date,
          'start_time' => $request->m_start_time[$key],
          'end_date' => $request->m_end_date[$key],
          'end_time' => $request->m_end_time[$key],
          'duration' => $diffent,
          'start_date_time' => $start,
          'end_date_time' => $end,
        ]);
        if ($i == 1) {
          $event->update([
            'duration' => $diffent
          ]);
        }
        $i++;
      }

      //update event date time
      $event_date = EventDates::where('event_id', $event->id)->orderBy('end_date_time', 'desc')->first();

      $event->end_date_time = $event_date->end_date_time;
      $event->save();
    }



    if ($request->hasFile('slider_images')) {

      foreach ($request->file('slider_images') as $img) {

        // unique filename
        $filename = uniqid() . '.' . $img->getClientOriginalExtension();

        $uploadPath = public_path('assets/admin/img/event-gallery/');

        if (!file_exists($uploadPath)) {
          mkdir($uploadPath, 0775, true);
        }

        // move image
        $img->move($uploadPath, $filename);

        // save to DB
        $pi = new EventImage();
        $pi->event_id = $event->id ?? null;
        $pi->image    = $filename;
        $pi->save();
      }
    }

    $in['event_id'] = $event->id;
    if ($request->event_type == 'online') {
      if (!$request->pricing_type) {
        $in['pricing_type'] = 'normal';
      }
      $in['early_bird_discount'] = $request->early_bird_discount_type;
      $in['early_bird_discount_type'] = $request->discount_type;
      $ticket = Ticket::create($in);
    }

    $slders = $request->slider_images;

    foreach ($slders as $key => $id) {
      $event_image = EventImage::where('id', $id)->first();
      if ($event_image) {
        $event_image->event_id = $event->id;
        $event_image->save();
      }
    }
    $languages = Language::all();

    foreach ($languages as $language) {
      $event_content = new EventContent();
      $event_content->language_id = $language->id;
      $event_content->event_category_id = $request[$language->code . '_category_id'];
      $event_content->event_id = $event->id;
      $event_content->title = $request[$language->code . '_title'];
      if ($request->event_type == 'venue') {
        $event_content->address = $request[$language->code . '_address'];
        $event_content->country_id = $request[$language->code . '_country'];
        $event_content->city_id = $request[$language->code . '_city'];
        $event_content->state_id = $request[$language->code . '_state'];
        $event_content->zip_code = $request[$language->code . '_zip_code'];
      }
      $event_content->slug = createSlug($request[$language->code . '_title']);
      $event_content->description = Purifier::clean($request[$language->code . '_description'], 'youtube');
      $event_content->refund_policy = $request[$language->code . '_refund_policy'];
      $event_content->meta_keywords = $request[$language->code . '_meta_keywords'];
      $event_content->meta_description = $request[$language->code . '_meta_description'];
      $event_content->save();
    }

    return response()->json([
      'success' => true,
      'message' => __('Event added successfully!')
    ]);
  }
  /**
   * Update status (active/DeActive) of a specified resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function updateStatus(Request $request)
  {
    $rules = [
      'event_id' => 'required',
      'status' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'errors' => $validator->errors()
      ], 422);
    }


    $organizer = Auth::guard('organizer_sanctum')->user();

    $event = Event::where('id', $request->event_id)
      ->where('organizer_id', $organizer->id)
      ->first();

    if (! $event) {
      return response()->json([
        'success' => false,
        'message' => 'You are not authorized to update this event.'
      ], 403);
    }
    $event->update([
      'status' => $request['status']
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Updated Successfully'
    ]);
  }
  /**
   * Update featured status of a specified resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function updateFeatured(Request $request)
  {
    $rules = [
      'event_id' => 'required',
      'is_featured' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'errors' => $validator->errors()
      ], 422);
    }
    $organizer = Auth::guard('organizer_sanctum')->user();

    $event = Event::where('id', $request->event_id)
      ->where('organizer_id', $organizer->id)
      ->first();

    if (! $event) {
      return response()->json([
        'success' => false,
        'message' => 'You are not authorized to update this event.'
      ], 403);
    }
    $event->update([
      'is_featured' => $request['is_featured']
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Updated Successfully'
    ]);
  }

  public function edit($id)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $event = Event::with('ticket')->where([['id', $id], ['organizer_id', $organizer_id]])->first();
    if (!$event) {
      return response()->json([
        'success' => false,
        'message' => "You are not authorized to access this event."
      ]);
    }
    $event->thumbnail = HelperController::getImagePath('assets/admin/img/event/thumbnail/', $event->thumbnail);
    $event_contents = EventContent::Where('event_id', $id)->get();
    Log::info($event_contents);
    $event_images = EventImage::where('event_id', $id)
      ->select('id', 'image')
      ->get()
      ->map(function ($item) {
        return [
          'id' => $item->id,
          'image' => asset('assets/admin/img/event-gallery/' . $item->image),
        ];
      });

    if ($event->date_type === 'multiple') {
      $event_dates = $event->dates()->get();
    } else {
      $event_dates = [];
    }

    $information['event'] = $event;
    $information['event_contents'] = $event_contents;
    $information['event_images'] = $event_images;
    $information['event_dates'] = $event_dates;
    $mapStatus = DB::table('basic_settings')->pluck('google_map_status')->first();
    $defaultLang = Language::where('is_default', 1)->first();
    if ($mapStatus == 1) {
      $information['event_address'] = EventContent::select('address')
        ->where(['event_id' => $id, 'language_id' => $defaultLang->id])
        ->first();
    }

    $information['getCurrencyInfo']  = $this->getCurrencyInfo();
    $information['languages'] = Language::all();


    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  public function images($portid)
  {
    $images = EventImage::where('event_id', $portid)->get();
    return $images;
  }

  public function update(Request $request)
  {
    $rules = [
      'event_id' => 'required',
      'event_type' => 'required',
      'date_type' => 'required',
      'countdown_status' => 'required',
      'slider_images'     => 'sometimes|array',
      'slider_images.*' => [
        'sometimes',
        'image',
        'mimes:jpg,jpeg,png',
        function ($attribute, $value, $fail) {
          if ($value && is_file($value->getPathname())) {
            [$width, $height] = getimagesize($value->getPathname());

            if ($width != 1170 || $height != 570) {
              $fail('Each slider image must be exactly 1170x570 pixels.');
            }
          }
        },
      ],

      'thumbnail' => [
        'sometimes',
        new ImageMimeTypeRule(),
        function ($attribute, $value, $fail) {
          if ($value && is_file($value->getPathname())) {
            [$width, $height] = getimagesize($value->getPathname());
            if ($width != 320 || $height != 230) {
              $fail(__('The thumbnail image dimensions must be exactly 320x230 pixels.'));
            }
          }
        }
      ],
      'status'      => 'required',
      'is_featured' => 'required',
    ];

    /* -------------------- DATE TYPE -------------------- */
    if ($request->date_type === 'single') {
      $rules['start_date'] = 'required';
      $rules['start_time'] = 'required';
      $rules['end_date']   = 'required';
      $rules['end_time']   = 'required';
    }
    if ($request->date_type === 'multiple') {
      $rules['m_start_date']   = 'required|array';
      $rules['m_start_date.*'] = 'required|date';

      $rules['m_start_time']   = 'required|array';
      $rules['m_start_time.*'] = 'required';

      $rules['m_end_date']     = 'required|array';
      $rules['m_end_date.*']   = 'required|date';

      $rules['m_end_time']     = 'required|array';
      $rules['m_end_time.*']   = 'required';
    }


    /* -------------------- EVENT TYPE : ONLINE -------------------- */
    if ($request->event_type === 'online') {

      $rules['early_bird_discount_type'] = 'required';
      $rules['meeting_url'] = 'required|url';
      $rules['discount_type'] = 'required_if:early_bird_discount_type,enable';
      $rules['early_bird_discount_amount'] = 'required_if:early_bird_discount_type,enable';
      $rules['early_bird_discount_date'] = 'required_if:early_bird_discount_type,enable';
      $rules['early_bird_discount_time'] = 'required_if:early_bird_discount_type,enable';
      $rules['ticket_available_type'] = 'required';
      $rules['max_ticket_buy_type'] = 'required';

      if ($request->filled('ticket_available_type') && $request->ticket_available_type === 'limited') {
        $rules['ticket_available'] = 'required';
      }

      if ($request->filled('max_ticket_buy_type') && $request->max_ticket_buy_type === 'limited') {
        $rules['max_buy_ticket'] = 'required';
      }

      if (!$request->filled('pricing_type')) {
        $rules['price'] = 'required';
      }

      if (
        $request->early_bird_discount_type === 'enable' &&
        $request->discount_type === 'percentage'
      ) {
        $rules['early_bird_discount_amount'] = 'numeric|between:1,99';
      }

      if (
        $request->early_bird_discount_type === 'enable' &&
        $request->discount_type === 'fixed'
      ) {
        $price = max(($request->price ?? 1) - 1, 1);
        $rules['early_bird_discount_amount'] = "numeric|between:1,$price";
      }
    }

    /* -------------------- EVENT TYPE : VENUE -------------------- */
    if ($request->event_type === 'venue') {
      $rules['latitude']  = 'required_if:event_type,venue';
      $rules['longitude'] = 'required_if:event_type,venue';
    }

    /* -------------------- BASIC SETTINGS -------------------- */
    $bs = DB::table('basic_settings')
      ->select('event_country_status', 'event_state_status')
      ->first();

    /* -------------------- LANGUAGE BASED RULES -------------------- */
    $languages = Language::all();

    foreach ($languages as $language) {

      $titleField = $language->code . '_title';
      $slug = createSlug($request->$titleField);

      $id = $request->event_id;
      $rules[$language->code . '_title'] = [
        'required',
        'max:255',
        function ($attribute, $value, $fail) use ($slug, $id, $language) {
          $cis = EventContent::where('event_id', '<>', $id)->where('language_id', $language->id)->get();
          foreach ($cis as $key => $ci) {
            if (strtolower($slug) == strtolower($ci->slug)) {
              $fail('The title field must be unique for ' . $language->name . ' language.');
            }
          }
        }
      ];

      if ($bs->event_country_status == 1) {
        $rules[$language->code . '_country'] = 'required_if:event_type,venue';
      }

      $totalState = EventState::where('language_id', $language->id)->count();

      if ($bs->event_state_status == 1 && $totalState > 0) {
        $rules[$language->code . '_state'] = 'required_if:event_type,venue';
      }

      $rules[$language->code . '_category_id'] = 'required';
      $rules[$language->code . '_description'] = 'min:30';
      $rules[$language->code . '_address'] = 'required_if:event_type,venue';
      $rules[$language->code . '_city'] = 'required_if:event_type,venue';
    }

    /* -------------------- VALIDATOR -------------------- */
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors'  => $validator->errors()->toArray()
      ], 400);
    }



    if ($request->hasFile('slider_images')) {

      foreach ($request->file('slider_images') as $img) {

        // unique filename
        $filename = uniqid() . '.' . $img->getClientOriginalExtension();

        $uploadPath = public_path('assets/admin/img/event-gallery/');

        if (!file_exists($uploadPath)) {
          mkdir($uploadPath, 0775, true);
        }

        // move image
        $img->move($uploadPath, $filename);

        // save to DB
        $pi = new EventImage();
        $pi->event_id = $request->event_id ?? null;
        $pi->image    = $filename;
        $pi->save();
      }
    }


    //calculate duration
    if ($request->date_type == 'single') {
      $start = Carbon::parse($request->start_date . $request->start_time);
      $end =  Carbon::parse($request->end_date . $request->end_time);
      $diffent = DurationCalulate($start, $end);
    }
    //calculate duration end
    $img = $request->file('thumbnail');

    $in = $request->all();

    $event = Event::where('id', $request->event_id)->first();
    if ($request->hasFile('thumbnail')) {
      @unlink(public_path('assets/admin/img/event/thumbnail/') . $event->thumbnail);
      $filename = time() . '.' . $img->getClientOriginalExtension();
      @mkdir(public_path('assets/admin/img/event/thumbnail/'), 0775, true);
      $request->file('thumbnail')->move(public_path('assets/admin/img/event/thumbnail/'), $filename);
      $in['thumbnail'] = $filename;
    }

    $languages = Language::all();

    $i = 1;
    foreach ($languages as $language) {
      $event_content = EventContent::where('event_id', $event->id)->where('language_id', $language->id)->first();
      if (!$event_content) {
        $event_content = new EventContent();
      }
      $event_content->language_id = $language->id;
      $event_content->event_category_id = $request[$language->code . '_category_id'];
      $event_content->event_id = $event->id;
      $event_content->title = $request[$language->code . '_title'];
      if ($request->event_type == 'venue') {
        $event_content->address = $request[$language->code . '_address'];
        $event_content->country_id = $request[$language->code . '_country'];
        $event_content->city_id = $request[$language->code . '_city'];
        $event_content->state_id = $request[$language->code . '_state'];
        $event_content->zip_code = $request[$language->code . '_zip_code'];
      }
      $event_content->slug = createSlug($request[$language->code . '_title']);
      $event_content->description = Purifier::clean($request[$language->code . '_description'], 'youtube');
      $event_content->refund_policy = $request[$language->code . '_refund_policy'];
      $event_content->meta_keywords = $request[$language->code . '_meta_keywords'];
      $event_content->meta_description = $request[$language->code . '_meta_description'];
      $event_content->save();
    }
    if ($request->event_type == 'online') {
      if (!$request->pricing_type) {
        $pricing_type = 'normal';
      } else {
        $pricing_type = $request->pricing_type;
      }
      Ticket::where('event_id', $request->event_id)->update([
        'price' => $request->price,
        'f_price' => $request->price,
        'pricing_type' => $pricing_type,
        'ticket_available_type' => $request->ticket_available_type,
        'ticket_available' => $request->ticket_available,
        'max_ticket_buy_type' => $request->max_ticket_buy_type,
        'max_buy_ticket' => $request->max_buy_ticket,
        'early_bird_discount' => $request->early_bird_discount_type,
        'early_bird_discount_type' => $request->discount_type,
        'early_bird_discount_amount' => $request->early_bird_discount_amount,
        'early_bird_discount_date' => $request->early_bird_discount_date,
        'early_bird_discount_time' => $request->early_bird_discount_time,
      ]);
    }

    $event = Event::where('id', $event->id)->first();

    if ($request->date_type == 'multiple') {
      $i = 1;
      foreach ($request->m_start_date as $key => $date) {
        $start = Carbon::parse($date . $request->m_start_time[$key]);
        $end =  Carbon::parse($request->m_end_date[$key] . $request->m_end_time[$key]);
        $diffent = DurationCalulate($start, $end);

        if (!empty($request->date_ids[$key])) {
          $event_date = EventDates::where('id', $request->date_ids[$key])->first();
          $event_date->start_date = $date;
          $event_date->start_time = $request->m_start_time[$key];
          $event_date->end_date = $request->m_end_date[$key];
          $event_date->end_time = $request->m_end_time[$key];
          $event_date->duration = $diffent;
          $event_date->start_date_time = $start;
          $event_date->end_date_time = $end;
          $event_date->save();
        } else {
          EventDates::create([
            'event_id' => $event->id,
            'start_date' => $date,
            'start_time' => $request->m_start_time[$key],
            'end_date' => $request->m_end_date[$key],
            'end_time' => $request->m_end_time[$key],
            'duration' => $diffent,
            'start_date_time' => $start,
            'end_date_time' => $end,
          ]);
        }
        if ($i == 1) {
          $event->update([
            'duration' => $diffent
          ]);
        }
        $i++;
      }
    }

    if ($request->date_type == 'single') {
      $in['end_date_time'] = Carbon::parse($request->end_date . ' ' . $request->end_time);
      $in['duration'] = $diffent;
    } else {
      //update event date time
      $event_date = EventDates::where('event_id', $event->id)->orderBy('end_date_time', 'desc')->first();

      $in['end_date_time'] = $event_date->end_date_time;
    }

    $event->update($in);

    return response()->json([
      'success' => true,
      'message' => __('Event Updated successfully!')
    ]);
  }
  public function deleteDate(Request $request)
  {
    $rules = [
      'date_id' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'errors' => $validator->errors()
      ], 422);
    }
    $date = EventDates::where('id', $request->date_id)->first();

    if (! $date) {
      return response()->json([
        'success' => false,
        'message' => 'Event date not found.'
      ], 404);
    }

    $organizer = Auth::guard('organizer_sanctum')->user();

    $event = Event::where('id', $date->event_id)
      ->where('organizer_id', $organizer->id)
      ->first();

    if (! $event) {
      return response()->json([
        'success' => false,
        'message' => 'You are not authorized to delete this event date.'
      ], 403);
    }

    $date->delete();

    return response()->json([
      'success' => true,
      'message' => 'Event date deleted successfully.'
    ]);
  }
  public function imagedbrmv(Request $request)
  {
    $rules = [
      'id' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'errors' => $validator->errors()
      ], 422);
    }
    $image = EventImage::where('id', $request->id)->first();

    if (!$image) {
      return response()->json([
        'success' => false,
        'message' => 'Image not found.'
      ], 404);
    }

    $organizer = Auth::guard('organizer_sanctum')->user();

    $event = Event::where('id', $image->event_id)
      ->where('organizer_id', $organizer->id)
      ->first();

    if (! $event) {
      return response()->json([
        'success' => false,
        'message' => 'You are not authorized to delete this event date.'
      ], 403);
    }

    $image_count = EventImage::where('event_id', $image->event_id)->get()->count();

    if ($image_count > 1) {
      @unlink(public_path('assets/admin/img/event-gallery/') . $image->image);
      $image->delete();
      return response()->json([
        'success' => true,
        'message' => 'Image deleted successfully.'
      ]);
    } else {
      return response()->json([
        'success' => false,
        'message' => 'You can\'t delete all images!'
      ]);
    }
  }


  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $event = Event::where([['id', $id], ['organizer_id', $organizer_id]])->first();
    if (!$event) {
      return response()->json(['success' => false, 'message' => "You are not authorized to access this event."]);
    }
    if (!app(\App\Services\Events\EventDeletionService::class)->delete($event)) {
      return response()->json(['success' => false, 'message' => __(\App\Services\Events\EventDeletionService::BLOCKED_MESSAGE)], 422);
    }
    return response()->json(['success' => true, 'message' => __('Event Deleted Successfully!')]);
  }
  //bulk_delete
  public function bulk_delete(Request $request)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $kept = 0;
    foreach ((array) $request->ids as $id) {
      $event = Event::where([['id', $id], ['organizer_id', $organizer_id]])->first();
      if (!$event) {
        return response()->json(['success' => false, 'message' => "You are not authorized to access this event."]);
      }
      if (!app(\App\Services\Events\EventDeletionService::class)->delete($event)) $kept++;
    }
    return response()->json(['success' => $kept === 0, 'message' => $kept ? __(':count event(s) with bookings or payments were kept; set them to inactive instead.', ['count' => $kept]) : __('Event Deleted Successfully!')]);
  }
  public function editTicketSetting($id)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $event = Event::where('organizer_id', $organizer_id)
      ->where('id', $id)
      ->select('ticket_image', 'ticket_logo', 'instructions')
      ->first();


    if (!$event) {
      return response()->json([
        'success' => false,
        'message' => 'You are not authorized to see this event.'
      ], 403);
    }
    if ($event->ticket_image) {
      $event->ticket_image = HelperController::getImagePath('assets/admin/img/event_ticket/', $event->ticket_image);
    }
    if ($event->ticket_logo) {
      $event->ticket_logo = HelperController::getImagePath('assets/admin/img/event_ticket_logo/', $event->ticket_logo);
    }
    $information['event'] = $event;

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }
  public function updateTicketSetting(TicketSettingRequest $request)
  {

    $rules = [
      'event_id' => 'required',
      'ticket_image' => [
        'sometimes',
        new ImageMimeTypeRule(),
      ],
      'ticket_logo' => [
        'sometimes',
        new ImageMimeTypeRule(),
      ],
    ];


    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $event = Event::where([['id', $request->event_id], ['organizer_id', $organizer_id]])->first();
    if (!$event) {
      return response()->json([
        'success' => false,
        'message' => "You are not authorized to access this event."
      ]);
    }

    $ticket_image = $request->file('ticket_image');
    $ticket_logo = $request->file('ticket_logo');
    $in = $request->all();
    $instructions = Purifier::clean($request->instructions);
    $event = Event::where('id', $request->event_id)->first();
    if ($request->hasFile('ticket_image')) {
      @unlink(public_path('assets/admin/img/event_ticket/') . $event->ticket_image);
      $filename = time() . rand(111, 999) . '.' . $ticket_image->getClientOriginalExtension();
      @mkdir(public_path('assets/admin/img/event_ticket/'), 0775, true);
      $request->file('ticket_image')->move(public_path('assets/admin/img/event_ticket/'), $filename);
      $in['ticket_image'] = $filename;
    }
    if ($request->hasFile('ticket_logo')) {
      @unlink(public_path('assets/admin/img/event_ticket_logo/') . $event->ticket_logo);
      $filename = time() . rand(111, 999) . '.' . $ticket_logo->getClientOriginalExtension();
      @mkdir(public_path('assets/admin/img/event_ticket_logo/'), 0775, true);
      $request->file('ticket_logo')->move(public_path('assets/admin/img/event_ticket_logo/'), $filename);
      $in['ticket_logo'] = $filename;
    }
    $in['instructions'] = $instructions;

    $event->update($in);
    return response()->json([
      'success' => true,
      'message' => "Ticket Setting Updated Sucessfully"
    ]);
  }

  //search country
  public function getCountry(Request $request)
  {
    $search = $request->input('search');
    $page = $request->input('page', 1);
    $pageSize = 10;

    $query = EventCountry::where('language_id', $request->lang);

    if ($search) {
      $query->where('name', 'like', "%{$search}%");
    }

    // Add pagination
    $countries = $query->skip(($page - 1) * $pageSize)
      ->take($pageSize + 1)
      ->get(['id', 'slug', 'name']);


    // Check if there's more data
    $hasMore = count($countries) > $pageSize;
    $results = $hasMore ? $countries->slice(0, $pageSize) : $countries;

    return response()->json([
      'results' => $results,
      'more' => $hasMore
    ]);
  }


  public function searchSate(Request $request)
  {
    $search = $request->input('search');
    $page = $request->input('page', 1);
    $pageSize = 10;

    $country_id = $request->country;

    $query = EventState::where('language_id', $request->lang)
      ->when($request->country, function ($q) use ($country_id) {
        return $q->where('country_id', $country_id);
      });

    if ($search) {
      $query->where('name', 'like', "%{$search}%");
    }

    // Add pagination
    $cities = $query->skip(($page - 1) * $pageSize)
      ->take($pageSize + 1)
      ->get(['id', 'slug', 'name']);

    // Check if there's more data
    $hasMore = count($cities) > $pageSize;
    $results = $hasMore ? $cities->slice(0, $pageSize) : $cities;

    return response()->json([
      'results' => $results,
      'more' => $hasMore
    ]);
  }


  public function getSearchCity(Request $request)
  {
    $search = $request->input('search');
    $page = $request->input('page', 1);
    $pageSize = 10;

    $state_id = $request->state;

    $query = EventCity::where('language_id', $request->lang)
      ->when($request->state, function ($q) use ($state_id) {
        return $q->where('state_id', $state_id);
      });

    if ($search) {
      $query->where('name', 'like', "%{$search}%");
    }

    // Add pagination
    $cities = $query->skip(($page - 1) * $pageSize)
      ->take($pageSize + 1)
      ->get(['id', 'slug', 'name']);

    // Check if there's more data
    $hasMore = count($cities) > $pageSize;
    $results = $hasMore ? $cities->slice(0, $pageSize) : $cities;

    return response()->json([
      'results' => $results,
      'more' => $hasMore
    ]);
  }


  /**
   * get cities or states
   */
  public function get_state(Request $request)
  {
    $event_state_status = DB::table('basic_settings')
      ->pluck('event_state_status')->first();

    //if event state status is off then return cities
    if ($event_state_status == 0) {
      $cities =  EventCity::where('country_id', $request->country_id)->exists();
      return response()->json(['cities' => $cities], 200);
    }

    //if event state status is on then return states
    $states =  EventState::where('country_id', $request->country_id)->exists();
    return response()->json(['states' => $states], 200);
  }

  /**
   * get cities
   */
  public function getcities(Request $request)
  {
    $cities =  EventCity::where('state_id', $request->state_id)->select('id', 'name')->get();

    if (count($cities) > 0) {
      return response()->json(['cities' => $cities], 200);
    }

    return response()->json(['cities' => 'no_data_found'], 200);
  }
}
