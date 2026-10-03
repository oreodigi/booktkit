<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Exports\BookingExportApp;
use App\Http\Controllers\Api\HelperController;
use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\Event\Booking;
use App\Models\Event\EventContent;
use App\Models\PaymentGateway\OfflineGateway;
use App\Models\PaymentGateway\OnlineGateway;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

class BookingController extends Controller
{
  public function index(Request $request)
  {
    //get language
    $language = HelperController::getAppLanguage($request);
    $bookingId = $paymentStatus = null;
    $eventIds = [];
    if ($request->filled('booking_id')) {
      $bookingId = $request['booking_id'];
    }

    if ($request->filled('event_title')) {
      $event_contents = EventContent::where('title', 'like', '%' . $request->event_title . '%')->get();
      foreach ($event_contents as $event_content) {
        if (!in_array($event_content->event_id, $eventIds)) {
          array_push($eventIds, $event_content->event_id);
        }
      }
    }

    if ($request->filled('status')) {
      $paymentStatus = $request['status'];
    }

    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;

    $bookings = Booking::join('events', 'events.id', 'bookings.event_id')
      ->join('event_contents', 'event_contents.event_id', '=', 'events.id')
      ->where('event_contents.language_id', '=', $language->id)
      ->when($bookingId, function ($query, $bookingId) {
        return $query->where('bookings.booking_id', 'like', '%' . $bookingId . '%');
      })
      ->when($eventIds, function ($query) use ($eventIds) {
        return $query->whereIn('event_id', $eventIds);
      })
      ->when($paymentStatus, function ($query, $paymentStatus) {
        return $query->where('bookings.paymentStatus', '=', $paymentStatus);
      })
      ->select('bookings.*', 'event_contents.title')
      ->where('events.organizer_id', $organizer_id)
      ->orderByDesc('id')
      ->paginate(10);

    $information['bookings'] = $bookings;

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  //show
  public function show(Request $request, $id)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $booking = Booking::where([['id', $id], ['organizer_id', $organizer_id]])->first();

    if (!$booking) {
      return response()->json([
        'success' => false,
        'message' => "Booking Not Found!"
      ]);
    }
    $information['booking'] = $booking;

    //get language
    $language = HelperController::getAppLanguage($request);

    $eventContent = EventContent::where('event_id', $booking->event_id)->where('language_id', $language->id)->first();
    if (empty($eventContent)) {
      $eventContent = EventContent::where('event_id', $booking->event_id)->first();
    }
    $information['eventContent'] = $eventContent;

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  public function destroy($id)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $booking = Booking::where([['id', $id], ['organizer_id', $organizer_id]])->first();

    if (!$booking) {
      return response()->json([
        'success' => false,
        'message' => "Booking Not Found!"
      ]);
    }

    // first, delete the attachment
    @unlink(public_path('assets/admin/file/attachments/') . $booking->attachment);

    // second, delete the invoice
    @unlink(public_path('assets/admin/file/invoices/') . $booking->invoice);

    $booking->delete();

    return response()->json([
      'success' => true,
      'message' => "Booking deleted successfully!"
    ]);
  }

  public function bulkDestroy(Request $request)
  {
    $ids = $request->ids;
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;

    foreach ($ids as $id) {
      $booking = Booking::where([['id', $id], ['organizer_id', $organizer_id]])->first();

      if (!$booking) {
        return response()->json([
          'success' => false,
          'message' => "Booking Not Found!"
        ]);
      }

      // first, delete the attachment
      @unlink(public_path('assets/admin/file/attachments/') . $booking->attachment);

      // second, delete the invoice
      @unlink(public_path('assets/admin/file/invoices/') . $booking->invoice);

      $booking->delete();
    }

    return response()->json([
      'success' => true,
      'message' => "Bookings deleted successfully!"
    ]);
  }

  public function report(Request $request)
  {
    $language = HelperController::getAppLanguage($request);

    $fromDate = $request->from_date;
    $toDate = $request->to_date;
    $paymentStatus = $request->payment_status;
    $paymentMethod = $request->payment_method;

    if (!empty($fromDate) && !empty($toDate)) {
      $bookings = Booking::join('events', 'bookings.event_id', 'events.id')
        ->join('event_contents', 'event_contents.event_id', 'bookings.event_id')
        ->where('event_contents.language_id', $language->id)
        ->where('events.organizer_id', Auth::guard('organizer_sanctum')->user()->id)
        ->when($fromDate, function ($query, $fromDate) {
          return $query->whereDate('bookings.created_at', '>=', Carbon::parse($fromDate));
        })->when($toDate, function ($query, $toDate) {
          return $query->whereDate('bookings.created_at', '<=', Carbon::parse($toDate));
        })->when($paymentMethod, function ($query, $paymentMethod) {
          return $query->where('bookings.paymentMethod', $paymentMethod);
        })->when($paymentStatus, function ($query, $paymentStatus) {
          return $query->where('bookings.paymentStatus', '=', $paymentStatus);
        })
        ->select('event_contents.title', 'event_contents.slug', 'bookings.*')
        ->orderByDesc('id');

      Session::put('booking_report', $bookings->get());
      $data['bookings'] = $bookings->paginate(10);
    } else {
      Session::put('booking_report', []);
      $data['bookings'] = [];
    }

    $data['onPms'] = OnlineGateway::where('status', 1)->get();
    $data['offPms'] = OfflineGateway::where('status', 1)->get();
    $data['deLang'] = $language;
    $data['abs'] = Basic::select('base_currency_symbol_position', 'base_currency_symbol')->first();

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  public function export(Request $request)
  {
    // $bookings = Session::get('booking_report');
    $bookings = $request->bookings;
    Log::info($bookings);
    if (empty($bookings) || count($bookings) == 0) {
      return response()->json([
        'success' => false,
        'data' => "ok"
      ]);
    }
    // return Excel::download(new BookingExport($bookings), 'bookings.csv');
    return Excel::download(
      new BookingExportApp(collect($bookings)),
      'bookings.csv'
    );
  }
}
