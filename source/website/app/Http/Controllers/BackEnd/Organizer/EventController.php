<?php

namespace App\Http\Controllers\BackEnd\Organizer;

use Carbon\Carbon;
use App\Http\Helpers\UploadFile;
use App\Models\City;
use App\Models\Event;
use App\Models\State;
use App\Models\Country;
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
use App\Services\Events\EventFormService;
use App\Services\Events\EventActor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\Event\EventFormRequest;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests\TicketSettingRequest;
use App\Models\OrganizerAiBalance;
use Illuminate\Support\Facades\Log;
use App\Services\Payments\PaidEventPayoutGuard;

class EventController extends Controller
{
  //index
  public function index(Request $request)
  {
    $information['langs'] = Language::all();

    $language = Language::where('code', $request->language)->firstOrFail();
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
      ->where('events.organizer_id', '=', Auth::guard('organizer')->user()->id)
      ->when($event_type, function ($query, $event_type) {
        if ($event_type === 'box_office') {
          return $query->where(function ($q) {
            $q->where('events.box_office_enabled', 1)
              ->orWhere('events.event_type', 'box_office');
          });
        }
        if ($event_type === 'venue') {
          return $query->where('events.event_type', 'venue')
            ->where(function ($q) {
              $q->whereNull('events.box_office_enabled')
                ->orWhere('events.box_office_enabled', 0);
            });
        }
        return $query->where('events.event_type', $event_type);
      })
      ->select('events.*', 'event_contents.id as eventInfoId', 'event_contents.title', 'event_contents.slug', 'event_categories.name as category')
      ->orderByDesc('events.id')
      ->paginate(10);

    $information['events'] = $events;
    return view('organizer.event.index', $information);
  }
  //choose_event_type
  public function choose_event_type()
  {
    return view('organizer.event.event_type');
  }
  //online_event
  public function add_event()
  {
    // get all the languages from db
    $languages = Language::get();
    $countries = Country::get();
    $information['getCurrencyInfo']  = $this->getCurrencyInfo();
    $information['languages'] = $languages;
    $information['countries'] = $countries;
  
    return view('organizer.event.create', $information);
  }
  //city_state
  public function city_state($id)
  {
    $city = City::where('country_id', $id)->orderBy('name', 'asc')->get();
    $state = State::where('country_id', $id)->orderBy('name', 'asc')->get();

    $result = [];
    $result['city'] = $city;
    $result['state'] = $state;
    return $result;
  }

  public function gallerystore(Request $request)
  {
    $hasFile = $request->hasFile('file');
    $imageUrl = trim((string) $request->input('image_url', ''));

    if (!$hasFile && $imageUrl === '') {
      return response()->json([
        'status' => 'error',
        'message' => 'Please upload an image file.',
        'file' => ['Please upload an image file.'],
        'error' => ['true']
      ], 422);
    }

    $uploadPath = public_path('assets/admin/img/event-gallery/');
    if (!file_exists($uploadPath)) {
      @mkdir($uploadPath, 0775, true);
    }

    $filename = uniqid() . '.jpg';
    $savePath = $uploadPath . $filename;

    if ($hasFile) {
      $validator = Validator::make($request->all(), [
        'file' => 'required|image|mimes:jpg,jpeg,png|max:1024'
      ], [
        'file.required' => 'Please upload an image file.',
        'file.image'    => 'The uploaded file must be an image.',
        'file.mimes'    => 'Only jpg, jpeg, and png files are allowed.'
      ]);

      if ($validator->fails()) {
        return response()->json([
          'status' => 'error',
          'message' => $validator->errors()->first(),
          'errors' => $validator->errors()
        ], 422);
      }

      $img = $request->file('file');
      $saved = $this->saveJpegImage($img->getPathname(), $savePath);
      if (!$saved) {
        return response()->json([
          'status' => 'error',
          'message' => 'The uploaded image could not be processed.'
        ], 422);
      }
    } else {
      $resolvedPath = UploadFile::resolveLocalSourcePath($imageUrl);

      if (!$resolvedPath || !is_file($resolvedPath)) {
        return response()->json([
          'status' => 'error',
          'message' => 'Generated image could not be prepared. Please try again.'
        ], 422);
      }

      $saved = $this->saveJpegImage($resolvedPath, $savePath);
      if (!$saved) {
        return response()->json([
          'status' => 'error',
          'message' => 'Generated image could not be processed. Please try again.'
        ], 422);
      }

      $this->deleteManagedGeneratedImage($resolvedPath);
    }

    $ownedEventId = null;
    if ($request->filled('event_id')) {
      $ownedEventId = Event::whereKey((int) $request->event_id)->where('organizer_id', Auth::guard('organizer')->id())->value('id');
      if (!$ownedEventId) {
        @unlink($savePath);
        return response()->json(['status' => 'error', 'message' => 'Event not found.'], 404);
      }
    }
    $pi = new EventImage;
    $pi->event_id = $ownedEventId;
    $pi->image = $filename;
    $pi->save();
    if (!$ownedEventId) \App\Support\GalleryUploadRegistry::remember((int) $pi->id);

    return response()->json([
      'status'  => 'success',
      'file_id' => $pi->id,
      'preview_url' => asset('assets/admin/img/event-gallery/' . $filename)
    ]);
  }

  private function saveJpegImage(
    string $sourcePath,
    string $destinationPath,
    ?int $targetWidth = null,
    ?int $targetHeight = null,
    bool $crop = false,
    int $quality = 90
  ): bool {
    if (!is_file($sourcePath)) {
      return false;
    }

    $binary = @file_get_contents($sourcePath);
    if ($binary === false) {
      return false;
    }

    $sourceImage = @imagecreatefromstring($binary);
    if (!$sourceImage) {
      return false;
    }

    $srcWidth = imagesx($sourceImage);
    $srcHeight = imagesy($sourceImage);
    $destWidth = $targetWidth ?: $srcWidth;
    $destHeight = $targetHeight ?: $srcHeight;

    if ($destWidth <= 0 || $destHeight <= 0) {
      imagedestroy($sourceImage);
      return false;
    }

    $srcX = 0;
    $srcY = 0;
    $srcCropWidth = $srcWidth;
    $srcCropHeight = $srcHeight;

    if ($crop && $targetWidth && $targetHeight) {
      $sourceRatio = $srcWidth / max($srcHeight, 1);
      $targetRatio = $targetWidth / max($targetHeight, 1);

      if ($sourceRatio > $targetRatio) {
        $srcCropWidth = (int) round($srcHeight * $targetRatio);
        $srcX = (int) floor(($srcWidth - $srcCropWidth) / 2);
      } else {
        $srcCropHeight = (int) round($srcWidth / $targetRatio);
        $srcY = (int) floor(($srcHeight - $srcCropHeight) / 2);
      }
    }

    $canvas = imagecreatetruecolor($destWidth, $destHeight);
    if (!$canvas) {
      imagedestroy($sourceImage);
      return false;
    }

    $background = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, 0, 0, $destWidth, $destHeight, $background);

    $savedResample = imagecopyresampled(
      $canvas,
      $sourceImage,
      0,
      0,
      $srcX,
      $srcY,
      $destWidth,
      $destHeight,
      $srcCropWidth,
      $srcCropHeight
    );

    if (!$savedResample) {
      imagedestroy($canvas);
      imagedestroy($sourceImage);
      return false;
    }

    $saved = imagejpeg($canvas, $destinationPath, $quality);

    imagedestroy($canvas);
    imagedestroy($sourceImage);

    return (bool) $saved;
  }

  private function deleteManagedGeneratedImage(?string $sourcePath): void
  {
    if (empty($sourcePath) || !is_file($sourcePath)) {
      return;
    }

    $realSourcePath = realpath($sourcePath);
    $managedDirectory = realpath(public_path('assets/img/ai/generated/'));

    if (!$realSourcePath || !$managedDirectory) {
      return;
    }

    $normalizedSourcePath = str_replace('\\', '/', $realSourcePath);
    $normalizedManagedDirectory = rtrim(str_replace('\\', '/', $managedDirectory), '/');

    if (strpos($normalizedSourcePath, $normalizedManagedDirectory . '/') !== 0) {
      return;
    }

    @unlink($realSourcePath);
  }

  public function imagermv(Request $request)
  {
    // Removing an upload that is not yet attached: only the uploader's session may do it.
    $pi = EventImage::whereKey((int) $request->fileid)->whereNull('event_id')->first();
    if (!$pi || !\App\Support\GalleryUploadRegistry::owns((int) $pi->id)) abort(404);
    @unlink(public_path('assets/admin/img/event-gallery/') . basename($pi->image));
    $pi->delete();
    return $pi->id;
  }

  private function storeGeneratedThumbnail(string $imageUrl, ?string $oldFile = null): ?string
  {
    $resolvedPath = UploadFile::resolveLocalSourcePath($imageUrl);

    if (!$resolvedPath || !is_file($resolvedPath)) {
      return null;
    }

    $directory = public_path('assets/admin/img/event/thumbnail/');
    @mkdir($directory, 0775, true);

    $filename = uniqid() . '.jpg';
    $saved = $this->saveJpegImage($resolvedPath, $directory . $filename, 320, 230);

    if (!$saved) {
      return null;
    }

    if (!empty($oldFile)) {
      @unlink($directory . $oldFile);
    }

    $this->deleteManagedGeneratedImage($resolvedPath);

    return $filename;
  }

  public function store(EventFormRequest $request, EventFormService $service)
  {
    $event = $service->createEvent($request->validated(), EventActor::organizer(Auth::guard('organizer')->user()));
    Session::flash('success', 'Added Successfully');
    return response()->json(['status' => 'success', 'redirect' => route('organizer.event_management.ticket_setting', ['id' => $event->id])], 200);
  }

  public function duplicate($id, EventFormService $service)
  {
    try {
      $source = Event::findOrFail($id);
      $duplicate = $service->duplicateEvent($source, EventActor::organizer(Auth::guard('organizer')->user()));
      return redirect()->route('organizer.event_management.edit_event', ['id' => $duplicate->id, 'mode' => 'duplicate'])
        ->with('success', 'Duplicated — review dates before publishing');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
      throw $e;
    } catch (\Throwable $e) {
      return redirect()->back()->with('error', 'Event could not be duplicated. Please try again.');
    }
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
    $request->validate(['status' => 'required|in:0,1']);
    $event = Event::whereKey($id)->where('organizer_id', Auth::guard('organizer')->id())->firstOrFail();

    if ((int)$request['status'] === 1 && !app(PaidEventPayoutGuard::class)->canPublish($event)) {
      Session::flash('warning', 'Complete Payouts & KYC before publishing an event with paid tickets. Free events can be published immediately.');
      return redirect()->back();
    }

    $event->update([
      'status' => (string) $request['status']
    ]);
    Session::flash('success', 'Updated Successfully');

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
    $request->validate(['is_featured' => 'required|in:yes,no']);
    $event = Event::whereKey($id)->where('organizer_id', Auth::guard('organizer')->id())->firstOrFail();
    $event->is_featured = $request['is_featured'];
    $event->save();
    Session::flash('success', 'Updated Successfully');

    return redirect()->back();
  }

  public function edit($id, EventFormService $service)
  {
    $event = Event::with('ticket')->findOrFail($id);
    $information = $service->formData($event, EventActor::organizer(Auth::guard('organizer')->user()));
    $information['mode'] = request('mode') === 'duplicate' ? 'duplicate' : 'edit';
    $mapStatus = DB::table('basic_settings')->pluck('google_map_status')->first();
    $defaultLang = Language::where('is_default', 1)->first();
    if ($mapStatus == 1 && $defaultLang) {
      $information['event_address'] = EventContent::select('address')->where(['event_id'=>$id,'language_id'=>$defaultLang->id])->first();
    }
    $information['getCurrencyInfo'] = $this->getCurrencyInfo();
    return view('organizer.event.edit', $information);
  }
  public function imagedbrmv(Request $request)
  {
    $organizerId = Auth::guard('organizer')->id();
    $pi = EventImage::whereKey((int) $request->fileid)
      ->whereIn('event_id', Event::where('organizer_id', $organizerId)->select('id'))->firstOrFail();
    if (EventImage::where('event_id', $pi->event_id)->count() <= 1) {
      return 'false';
    }
    @unlink(public_path('assets/admin/img/event-gallery/') . basename($pi->image));
    $pi->delete();
    return $pi->id;
  }
  public function images($portid)
  {
    $event = Event::whereKey((int) $portid)->where('organizer_id', Auth::guard('organizer')->id())->firstOrFail();
    return EventImage::where('event_id', $event->id)->get();
  }

  public function update(EventFormRequest $request, EventFormService $service)
  {
    $event = Event::findOrFail($request->event_id);
    $event = $service->updateEvent($event, $request->validated(), EventActor::organizer(Auth::guard('organizer')->user()));
    Session::flash('success', 'Updated Successfully');
    return response()->json(['status'=>'success','redirect'=>route('organizer.event_management.ticket_setting', ['id'=>$event->id])], 200);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    $event = Event::whereKey($id)->where('organizer_id', Auth::guard('organizer')->id())->firstOrFail();
    if (!app(\App\Services\Events\EventDeletionService::class)->delete($event)) {
      return redirect()->back()->with('warning', __(\App\Services\Events\EventDeletionService::BLOCKED_MESSAGE));
    }
    return redirect()->back()->with('success', 'Deleted Successfully');
  }
  //bulk_delete
  public function bulk_delete(Request $request)
  {
    $kept = 0;
    foreach ((array) $request->ids as $id) {
      $event = Event::whereKey($id)->where('organizer_id', Auth::guard('organizer')->id())->first();
      if ($event && !app(\App\Services\Events\EventDeletionService::class)->delete($event)) $kept++;
    }
    Session::flash($kept ? 'warning' : 'success', $kept ? __(':count event(s) with bookings or payments were kept; set them to inactive instead.', ['count' => $kept]) : 'Deleted Successfully');
    return response()->json(['status' => 'success'], 200);
  }
  public function editTicketSetting($id)
  {
    $event = Event::where('organizer_id',  Auth::guard('organizer')->user()->id)->with('ticket')->findOrFail($id);
    $information['event'] = $event;
    return view('organizer.event.ticket-settings', $information);
  }
  public function updateTicketSetting(TicketSettingRequest $request)
  {

    $ticket_image = $request->file('ticket_image');
    $ticket_logo = $request->file('ticket_logo');
    // Ticket settings may only change ticket presentation, never status, owner, type or access rules.
    $in = [];
    $instructions = Purifier::clean($request->instructions);
    $event = Event::where('organizer_id', Auth::guard('organizer')->id())->findOrFail($request->event_id);
    if ($request->boolean('remove_ticket_image') && $event->ticket_image) {
      @unlink(public_path('assets/admin/img/event_ticket/') . $event->ticket_image);
      $in['ticket_image'] = null;
    }
    if ($request->boolean('remove_ticket_logo') && $event->ticket_logo) {
      @unlink(public_path('assets/admin/img/event_ticket_logo/') . $event->ticket_logo);
      $in['ticket_logo'] = null;
    }
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
    $event->update(array_intersect_key($in, array_flip(['ticket_image', 'ticket_logo', 'instructions'])));
    Session::flash('success', 'Ticket settings saved. Add tickets for your event.');

    $language = Language::where('is_default', 1)->first() ?: Language::firstOrFail();
    return response()->json([
      'status' => 'success',
      'redirect' => route('organizer.event.ticket', [
        'language' => $language->code,
        'event_id' => $event->id,
        'event_type' => $event->event_type,
      ]),
    ], 200);
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