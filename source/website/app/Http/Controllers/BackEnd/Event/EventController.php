<?php

namespace App\Http\Controllers\BackEnd\Event;

use Carbon\Carbon;
use App\Models\City;
use App\Models\Event;
use App\Models\State;
use App\Models\Country;
use App\Models\Language;
use App\Models\Organizer;
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
use App\Services\Events\EventFormService;
use App\Services\Events\EventActor;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\Event\EventFormRequest;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests\TicketSettingRequest;

class EventController extends Controller
{
  //index
  public function index(Request $request)
  {
    $information['langs'] = Language::all();

    $language = Language::where('code', $request->language)->firstOrFail();
    $information['language'] = $language;

    $event_type = null;
    if (filled($request->event_type)) {
      $event_type = $request->event_type;
    }
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
      ->when($event_type, function ($query) use ($event_type) {
        return $query->where('events.event_type', $event_type);
      })
      ->select('events.*', 'event_contents.id as eventInfoId', 'event_contents.title', 'event_contents.slug', 'event_categories.name as category')
      ->orderByDesc('events.id')
      ->paginate(10);

    $information['events'] = $events;
    return view('backend.event.index', $information);
  }
  //choose_event_type
  public function choose_event_type()
  {
    return view('backend.event.event_type');
  }
  //online_event
  public function add_event()
  {
    $information = [];
    $languages = Language::get();
    $information['languages'] = $languages;
    // $countries = Country::get();
    // $information['countries'] = $countries;
    $organizers = Organizer::get();
    $information['organizers'] = $organizers;

    return view('backend.event.create', $information);
  }

  public function gallerystore(Request $request)
  {

    $rules = [
      'file' => 'required|image|mimes:jpg,jpeg,png|max:1024'
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
      'file_id' => $pi->id,
      'preview_url' => asset('assets/admin/img/event-gallery/' . $filename)
    ]);
  }


  public function imagermv(Request $request)
  {
    $pi = EventImage::where('id', $request->fileid)->first();
    @unlink(public_path('assets/admin/img/event-gallery/') . $pi->image);
    $pi->delete();
    return $pi->id;
  }

  public function store(EventFormRequest $request, EventFormService $service)
  {
    $event = $service->createEvent($request->validated(), EventActor::admin());
    Session::flash('success', 'Added Successfully');
    return response()->json(['status' => 'success', 'redirect' => route('admin.event_management.ticket_setting', ['id' => $event->id])], 200);
  }

  public function duplicate($id, EventFormService $service)
  {
    try {
      $source = Event::findOrFail($id);
      $duplicate = $service->duplicateEvent($source, EventActor::admin());
      return redirect()->route('admin.event_management.edit_event', ['id' => $duplicate->id, 'mode' => 'duplicate'])
        ->with('success', 'Duplicated — review dates before publishing');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
      throw $e;
    } catch (\Throwable $e) {
      return redirect()->back()->with('error', 'Event could not be duplicated. Please try again.');
    }
  }

  /**
   * delete events dates
   */
  public function deleteDate($id)
  {
    $date = EventDates::where('id', $id)->first();
    $date->delete();
    return 'success';
  }
  /**
   * Update status (active/DeActive) of a specified resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function updateStatus(Request $request, $id)
  {
    $event = Event::find($id);

    $event->update([
      'status' => $request['status']
    ]);
    Session::flash('success', 'Deleted Successfully');

    return redirect()->back();
  }
  /**
   * Update featured status of a specified resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function updateFeatured(Request $request, $id)
  {
    $event = Event::find($id);

    if ($request['is_featured'] == 'yes') {
      $event->is_featured = 'yes';
      $event->save();

      Session::flash('success', 'Updated Successfully');
    } else {
      $event->is_featured = 'no';
      $event->save();

      Session::flash('success', 'Updated Successfully');
    }

    return redirect()->back();
  }

  public function edit($id, EventFormService $service)
  {
    $event = Event::with('ticket')->findOrFail($id);
    $information = $service->formData($event, EventActor::admin());
    $information['mode'] = request('mode') === 'duplicate' ? 'duplicate' : 'edit';
    $mapStatus = DB::table('basic_settings')->pluck('google_map_status')->first();
    $defaultLang = Language::where('is_default', 1)->first();
    if ($mapStatus == 1 && $defaultLang) {
      $information['event_address'] = EventContent::select('address')->where(['event_id'=>$id,'language_id'=>$defaultLang->id])->first();
    }
    
    return view('backend.event.edit', $information);
  }
  public function imagedbrmv(Request $request)
  {
    $pi = EventImage::where('id', $request->fileid)->first();
    $event_id = $pi->event_id;
    $image_count = EventImage::where('event_id', $event_id)->get()->count();
    if ($image_count > 1) {
      @unlink(public_path('assets/admin/img/event-gallery/') . $pi->image);
      $pi->delete();
      return $pi->id;
    } else {
      return 'false';
    }
  }
  public function images($portid)
  {
    $images = EventImage::where('event_id', $portid)->get();
    return $images;
  }

  public function update(EventFormRequest $request, EventFormService $service)
  {
    $event = Event::findOrFail($request->event_id);
    $event = $service->updateEvent($event, $request->validated(), EventActor::admin());
    Session::flash('success', 'Updated Successfully');
    return response()->json(['status'=>'success','redirect'=>route('admin.event_management.ticket_setting', ['id'=>$event->id])], 200);
  }
  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    $event = Event::find($id);

    @unlink(public_path('assets/admin/img/event/thumbnail/') . $event->thumbnail);

    $event_contents = EventContent::where('event_id', $event->id)->get();
    foreach ($event_contents as $event_content) {
      $event_content->delete();
    }
    $event_images = EventImage::where('event_id', $event->id)->get();
    foreach ($event_images as $event_image) {
      @unlink(public_path('assets/admin/img/event-gallery/') . $event_image->image);
      $event_image->delete();
    }

    //bookings
    $bookings = $event->booking()->get();
    foreach ($bookings as $booking) {
      // first, delete the attachment
      @unlink(public_path('assets/admin/file/attachments/') . $booking->attachment);

      // second, delete the invoice
      @unlink(public_path('assets/admin/file/invoices/') . $booking->invoice);

      $booking->delete();
    }

    //tickets
    $tickets = $event->tickets()->get();
    foreach ($tickets as $ticket) {
      $ticket->delete();
    }
    //wishlists
    $wishlists = $event->wishlists()->get();
    foreach ($wishlists as $wishlist) {
      $wishlist->delete();
    }

    //dates
    $dates = $event->dates()->get();
    foreach ($dates as $date) {
      $date->delete();
    }

    // finally delete the event
    $event->delete();

    return redirect()->back()->with('success', 'Deleted Successfully');
  }
  //bulk_delete
  public function bulk_delete(Request $request)
  {
    foreach ($request->ids as $id) {
      $event = Event::find($id);

      @unlink(public_path('assets/admin/img/event/thumbnail/') . $event->thumbnail);

      $event_contents = EventContent::where('event_id', $event->id)->get();
      foreach ($event_contents as $event_content) {
        $event_content->delete();
      }
      $event_images = EventImage::where('event_id', $event->id)->get();
      foreach ($event_images as $event_image) {
        @unlink(public_path('assets/admin/img/event-gallery/') . $event_image->image);
        $event_image->delete();
      }

      //bookings
      $bookings = $event->booking()->get();
      foreach ($bookings as $booking) {
        // first, delete the attachment
        @unlink(public_path('assets/admin/file/attachments/') . $booking->attachment);

        // second, delete the invoice
        @unlink(public_path('assets/admin/file/invoices/') . $booking->invoice);

        $booking->delete();
      }

      //tickets
      $tickets = $event->tickets()->get();
      foreach ($tickets as $ticket) {
        $ticket->delete();
      }

      //wishlists
      $wishlists = $event->wishlists()->get();
      foreach ($wishlists as $wishlist) {
        $wishlist->delete();
      }

      //dates
      $dates = $event->dates()->get();
      foreach ($dates as $date) {
        $date->delete();
      }
      // finally delete the event
      $event->delete();
    }
    Session::flash('success', 'Deleted Successfully');
    return response()->json(['status' => 'success'], 200);
  }
  public function editTicketSetting($id)
  {
    $event = Event::with('ticket')->findOrFail($id);
    $information['event'] = $event;
    return view('backend.event.ticket-settings', $information);
  }
  public function updateTicketSetting(TicketSettingRequest $request)
  {
    $ticket_image = $request->file('ticket_image');
    $ticket_slot_image = $request->file('ticket_slot_image');
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
    if ($request->hasFile('ticket_slot_image')) {
      @unlink(public_path('assets/admin/img/event_ticket/') . $event->ticket_slot_image);
      $filename = time() . rand(111, 999) . '.' . $ticket_slot_image->getClientOriginalExtension();
      @mkdir(public_path('assets/admin/img/event_ticket/'), 0775, true);
      $request->file('ticket_slot_image')->move(public_path('assets/admin/img/event_ticket/'), $filename);
      $in['ticket_slot_image'] = $filename;
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
    Session::flash('success', 'Updated Successfully');

    return response()->json(['status' => 'success'], 200);
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
}