<?php

use App\Http\Controllers\Api\AdminScannerController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\Organizer\BookingController;
use App\Http\Controllers\Api\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Api\Organizer\OrganizerController as OrganizerManageController;
use App\Http\Controllers\Api\Organizer\SlotSeatController;
use App\Http\Controllers\Api\Organizer\SupportTicketController as OrganizerSupportTicketController;
use App\Http\Controllers\Api\Organizer\TicketController;
use App\Http\Controllers\Api\OrganizerController;
use App\Http\Controllers\Api\OrganizerScannerController;
use App\Http\Controllers\Api\ProductOrderController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\Organizer\WithdrawController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//guest customer routes
Route::get('/', [HomeController::class, 'index'])->name('api.index');
Route::get('/get-lang/{code}', [LanguageController::class, 'getLang']);
Route::get('/get-basic', [HomeController::class, 'getBasic'])->name('getBasic');
Route::get('/get-currency', [HomeController::class, 'getCurrency'])->name('getBasic');
Route::post('/push-notification-store-endpoint', [HomeController::class, 'pushNotificationStore']);
Route::post('/save-fcm-token', [FcmTokenController::class, 'store']);
Route::get('/get-notifications', [FcmTokenController::class, 'getNotifications']);

Route::prefix('events')->group(function () {
  Route::get('/', [EventController::class, 'index'])->name('api.events');
  Route::get('/details', [EventController::class, 'details'])->name('api.event.details');
  Route::get('/slot/seat-details', [EventController::class, 'slotMapping'])->name('api.event.slot_mapping_seat');
  Route::get('/categories', [EventController::class, 'categories'])->name('api.event.categories');
});

Route::post('/event/apply-coupon', [EventController::class, 'applyCoupon'])->name('api.event.apply_coupon');
Route::post('/event/checkout-verify', [EventController::class, 'checkoutVerify'])->name('api.event.checkout_verify');
Route::post('/event/verify-payment', [EventController::class, 'verifyPayment'])->name('api.event.payment_verify');
Route::post('/event-booking', [EventController::class, 'store_booking'])->name('api.event.booking.store');
Route::get('shop/', [ShopController::class, 'index'])->name('api.shop');
Route::get('product/details', [ShopController::class, 'details'])->name('api.product.details');

Route::post('product/review/store', [ShopController::class, 'store_review'])->name('api.product.review.store');

Route::prefix('organizers')->group(function () {
  Route::get('/', [OrganizerController::class, 'index'])->name('api.organizers.index');
  Route::get('/details/{id}', [OrganizerController::class, 'details'])->name('api.organizers.details');
  Route::post('/contact-mail', [OrganizerController::class, 'contactMail'])->name('api.organizers.contact');
});

Route::prefix('customer')->group(function () {
  Route::get('/signup', [CustomerController::class, 'signup'])->name('api.customer.signup');
  Route::post('/signup/submit', [CustomerController::class, 'signupSubmit'])->name('api.customer.signup_submit');

  //facebook
  Route::get('login/facebook/callback', [CustomerController::class, 'handleFacebookCallback']);
  Route::get('auth/facebook', [CustomerController::class, 'facebookRedirect']);

  //google
  Route::get('login/google/callback', [CustomerController::class, 'handleGoogleCallback']);
  Route::get('auth/google', [CustomerController::class, 'googleRedirect']);

  Route::get('/login', [CustomerController::class, 'login'])->name('api.customer.login');
  Route::get('/authentication-fail', [CustomerController::class, 'authentication_fail'])->name('api.customer.authentication.fail');
  Route::post('/login/submit', [CustomerController::class, 'loginSubmit'])->name('api.customer.login_submit');
  //forget password

  Route::post('/forget-password', [CustomerController::class, 'forget_mail'])->name('api.customer.forget_password');
  Route::post('/reset-password-update', [CustomerController::class, 'reset_password_submit'])->name('api.customer.update_reset_password');
});

/* ************************************
 * Customer dashboard routes are goes here
 * ************************************/
Route::prefix('/customers')->middleware('auth:sanctum')->group(function () {
  Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('api.customers.dashboard');

  /* ************************************
   * Event Bookings routes are goes here
   * ************************************/
  Route::get('/bookings', [CustomerController::class, 'bookings'])->name('api.customers.bookings');
  Route::get('/booking/details', [CustomerController::class, 'booking_details'])->name('api.customers.booking.details');

  /* ************************************
   * Event Bookings routes are goes here
   * ************************************/

  Route::prefix('wishlists')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('api.customers.wishlists.index');
    Route::post('/store', [WishlistController::class, 'store'])->name('api.customers.wishlists.store');
    Route::post('/delete', [WishlistController::class, 'delete'])->name('api.customers.wishlists.delete');
  });

  /* ************************************
   * Product order routes are goes here
   * ************************************/
  Route::get('/product-orders', [ProductOrderController::class, 'product_order'])->name('api.customers.product_orders');
  Route::get('/product-order/details', [ProductOrderController::class, 'product_order_details'])->name('api.customers.product_order.details');

  /* ************************************
   * Support ticket routes are goes here
   * ************************************/
  Route::get('/support-tickets', [SupportTicketController::class, 'index'])->name('api.customers.support_tickets');
  Route::get('/support-ticket/details', [SupportTicketController::class, 'details'])->name('api.customers.support_tickets.details');
  Route::post('/support-ticket/store', [SupportTicketController::class, 'store'])->name('api.customers.support_tickets.store');
  Route::post('/support-ticket/reply', [SupportTicketController::class, 'reply'])->name('api.customers.support_tickets.reply');

  //edit profile
  Route::get('/edit-profile', [CustomerController::class, 'edit_profile'])->name('api.customers.edit_profile');
  //update profile info
  Route::post('/update/profile', [CustomerController::class, 'update_profile'])->name('api.customers.update_profile');

  //update password
  Route::post('/update/password', [CustomerController::class, 'updated_password'])->name('api.customers.updated_password');

  Route::post('/logout', [CustomerController::class, 'logoutSubmit'])->name('api.customers.logout');
});

Route::prefix('/organizer')->group(function () {
  Route::post('/login/submit', [OrganizerScannerController::class, 'loginSubmit'])->name('api.organizer.login_submit');
  Route::get('/authentication-fail', [OrganizerScannerController::class, 'authentication_fail'])->name('api.organizer.authentication.fail');
  Route::middleware('auth:organizer_sanctum')->group(function () {
    Route::post('/check-qrcode', [OrganizerScannerController::class, 'check_qrcode'])->name('api.organizer.check-qrcode');
    Route::post('/logout', [OrganizerScannerController::class, 'logoutSubmit'])->name('api.organizer.logout');
  });
});

Route::prefix('/staff-scanner')->group(function () {
  Route::post('/login', 'ScannerApi\\StaffScannerController@login')->middleware('throttle:staff-login');
  Route::middleware(['auth:staff_sanctum','throttle:staff-api'])->group(function () {
    Route::get('/events', 'ScannerApi\\StaffScannerController@events');
    Route::post('/scan', 'ScannerApi\\StaffScannerController@scan');
    Route::post('/logout', 'ScannerApi\\StaffScannerController@logout');
  });
});

Route::prefix('/admin')->group(function () {
  Route::post('/login/submit', [AdminScannerController::class, 'loginSubmit'])->name('api.admin.login_submit');
  Route::get('/authentication-fail', [AdminScannerController::class, 'authentication_fail'])->name('api.admin.authentication.fail');
  Route::group(['middleware' => 'auth:admin_sanctum'], function ($e) {
    Route::post('/check-qrcode', [AdminScannerController::class, 'check_qrcode'])->name('api.admin.check-qrcode');
    Route::post('/logout', [AdminScannerController::class, 'logoutSubmit'])->name('api.admin.logout');
  });
});

//organizer Route


Route::get('/organizer/signup', [OrganizerManageController::class, 'signup'])->name('api.organizer.signup');
Route::post('/organizer/signup/submit', [OrganizerManageController::class, 'signupSubmit'])->name('api.organizer.create');
Route::get('/organizer/login', [OrganizerManageController::class, 'login'])->name('api.organizer.login');

Route::prefix('/organizer')->middleware('auth:organizer_sanctum')->group(function () {
  Route::get('/dashboard', [OrganizerManageController::class, 'dashboard'])->name('api.organizer.dashboard');
  Route::get('/monthly-income', [OrganizerManageController::class, 'monthlyIncome'])->name('api.organizer.monthly_income');
  Route::get('/edit-profile', [OrganizerManageController::class, 'editProfile'])->name('api.organizer.edit.profile');
  Route::post('/updated-profile', [OrganizerManageController::class, 'updateProfile'])->name('api.organizer.update_profile');
  Route::post('/updated-password', [OrganizerManageController::class, 'updatedPassword'])->name('api.organizer.update_password');

  Route::get('/transcation', [OrganizerManageController::class, 'transcation'])->name('api.organizer.transcation');

  Route::prefix('/event-management')->group(function () {
    Route::get('/events', [OrganizerEventController::class, 'index'])->name('api.organizer.event_management.events');
    Route::get('/add-event',  [OrganizerEventController::class, 'addEvent'])->name('api.organizer.event_management.event.add');

    Route::get('/all-categories/{language_id}',  [OrganizerEventController::class, 'allCategories'])->withoutMiddleware('auth:organizer_sanctum')->name('api.organizer.event_management.event.add.all_categories');
    Route::get('/all-countries/{language_id}',  [OrganizerEventController::class, 'allCountries'])->withoutMiddleware('auth:organizer_sanctum')->name('api.organizer.event_management.event.add.all_countries');
    Route::get('/all-states/{language_id}',  [OrganizerEventController::class, 'allStates'])->withoutMiddleware('auth:organizer_sanctum')->name('api.organizer.event_management.event.add.all_states');
    Route::get('/all-cities/{language_id}',  [OrganizerEventController::class, 'allCities'])->withoutMiddleware('auth:organizer_sanctum')->name('api.organizer.event_management.event.add.all_cities');
    Route::get('/country-wise-state-city/{country_id}',  [OrganizerEventController::class, 'city_state'])->withoutMiddleware('auth:organizer_sanctum')->name('api.organizer.event_management.event.add.city_state');
    Route::get('/state-wise-city/{state_id}',  [OrganizerEventController::class, 'city'])->withoutMiddleware('auth:organizer_sanctum')->name('api.organizer.event_management.event.add.state_wise_city');

    Route::post('event-store',  [OrganizerEventController::class, 'store'])->name('api.organizer.event_management.event.store');
    Route::get('/event-edit/{id}',  [OrganizerEventController::class, 'edit'])->name('api.organizer.event_management.event.edit');
    Route::post('/event-update', [OrganizerEventController::class, 'update'])->name('api.organizer.event_management.event.update');
    Route::post('/event-delete-date', [OrganizerEventController::class, 'deleteDate'])->name('api.organizer.event_management.event.delete.date');
    Route::post('/event-img-delete', [OrganizerEventController::class, 'imagedbrmv'])->name('api.organizer.event.imgdbrmv');

    Route::post('/event-update-status',  [OrganizerEventController::class, 'updateStatus'])->name('api.organizer.event_management.event.event_status');
    Route::post('/event-update-featured', [OrganizerEventController::class, 'updateFeatured'])->name('api.organizer.event_management.event.update_featured');

    //ticket settings
    Route::get('/edit-ticket-setting/{id}', [OrganizerEventController::class, 'editTicketSetting'])->name('api.organizer.event_management.ticket_setting');
    Route::post('/update-ticket-setting', [OrganizerEventController::class, 'updateTicketSetting'])->name('api.organizer.event_management.update_ticket_setting');

    //ticket management
    Route::prefix('/event')->group(function () {
      Route::get('/tickets', [TicketController::class, 'index'])->name('api.organizer.event.ticket');
      Route::get('/add-ticket', [TicketController::class, 'create'])->name('api.organizer.event.add.ticket');
      Route::post('/store-ticket', [TicketController::class, 'store'])->name('api.organizer.ticket_management.store_ticket');
      Route::get('/edit-ticket', [TicketController::class, 'edit'])->name('api.organizer.event.edit.ticket');
      Route::post('update-ticket', [TicketController::class, 'update'])->name('api.organizer.ticket_management.update_ticket');

      Route::post('delete-ticket',  [TicketController::class, 'destroy'])->name('api.organizer.ticket_management.delete_ticket');

      Route::post('bulk-delete-ticket',  [TicketController::class, 'bulk_delete'])->name('organizer.event_management.bulk_delete_event_ticket');
    });

    Route::post('/event-delete/{id}',  [OrganizerEventController::class, 'destroy'])->name('api.organizer.event_management.delete_event');
    Route::post('/event-bulk-delete',  [OrganizerEventController::class, 'bulk_delete'])->name('api.organizer.event_management.bulk_delete_event');
  });

  //withdraw management
  Route::prefix('withdraw')->group(function () {
    Route::get('/', [WithdrawController::class, 'index'])->name('api.organizer.withdraw');
    Route::get('/create', [WithdrawController::class, 'create'])->name('api.organizer.withdraw.create');
    Route::get('/get-method-input/{id}', [WithdrawController::class, 'getInputs'])->name('api.organizer.withdraw.get_inputs');
    Route::get('/calculation/{method_id}/{amount}', [WithdrawController::class, 'balanceCalculation'])->name('api.organizer.withdraw.balance_calculation');
    Route::post('/send-request', [WithdrawController::class, 'sendRequest'])
      ->name('api.organizer.withdraw.send-request');
    Route::post('/delete', [WithdrawController::class, 'delete'])
      ->name('api.organizer.withdraw.delete_withdraw');
    Route::post('/bulk-delete', [WithdrawController::class, 'bulkDelete'])
      ->name('api.organizer.withdraw.bulk_delete_withdraw');
  });

  //support ticket management
  Route::prefix('support-ticket')->group(function () {
    Route::get('/', [OrganizerSupportTicketController::class, 'index'])->name('api.organizer.support_tickets');
    Route::post('store', [OrganizerSupportTicketController::class, 'store'])->name('api.organizer.support_ticket.store');
    Route::get('message/{id}', [OrganizerSupportTicketController::class, 'message'])->name('api.organizer.support_tickets.message');
    Route::post('zip-upload', [OrganizerSupportTicketController::class, 'zip_file_upload'])->name('api.organizer.support_ticket.zip_file.upload');
    Route::post('reply/{id}', [OrganizerSupportTicketController::class, 'ticketreply'])->name('api.organizer.support_ticket.reply');
    Route::post('delete/{id}', [OrganizerSupportTicketController::class, 'delete'])->name('api.organizer.support_tickets.delete');
  });

  //Event Bookings 
  Route::prefix('event-booking')->group(function () {
    Route::get('/', [BookingController::class, 'index'])->name('api.organizer.event.booking');
    Route::get('/details/{id}', [BookingController::class, 'show'])->name('api.organizer.event_booking.details');
    Route::post('/delete/{id}', [BookingController::class, 'destroy'])->name('api.organizer.event_booking.delete');
    Route::post('/bulk-delete',  [BookingController::class, 'bulkDestroy'])->name('api.organizer.event_booking.bulk_delete');
    Route::get('/report', [BookingController::class, 'report'])->name('api.organizer.event_booking.report');

    Route::get('/export', [BookingController::class, 'export'])->name('api.organizer.event_bookings.export');
  });

  // seat mapping
  Route::prefix('/seat-mapping')->group(function () {
    //toggle option slot
    Route::post('/toggle-option-slot', [SlotSeatController::class, 'slotAction'])->name('api.organizer.event_management.seat_mapping.action');
    //slot
    Route::prefix('/slot')->group(function ($e) {
      Route::get('/all', [SlotSeatController::class, 'allSlot'])->name('api.organizer.event_management.seat_mapping');
      Route::post('/update-background-image', [SlotSeatController::class, 'storeBackgroundImage'])->name('api.organizer.event_management.seat_mapping.store_ticket');

      Route::post('/update-store', [SlotSeatController::class, 'slotStoreUpdate'])->name('api.organizer.event_management.seat_mapping.slot.store_update');

      Route::post('/drag-drop', [SlotSeatController::class, 'slotDrupDrop'])->name('api.organizer.event_management.seat_mapping.slot.drup_drop');

      Route::post('/delete', [SlotSeatController::class, 'slotDelete'])->name('api.organizer.event_management.seat_mapping.slot.delete');

      Route::prefix('/seats')->group(function ($e) {
        Route::get('/', [SlotSeatController::class, 'slotSeat'])->name('api.organizer.event_management.seat_mapping.slot.seat_mapping');
        Route::post('/update', [SlotSeatController::class, 'slotSeatUpdate'])->name('api.organizer.event_management.seat_mapping.slot.seat_mapping_update');
      });
    });
  });
});

// BookTKIT payment orchestration v1
Route::prefix('v1/payments')->group(function () {
  Route::post('razorpay/order', 'Api\PaymentController@createRazorpayOrder');
  Route::post('razorpay/verify', 'Api\PaymentController@verifyRazorpay');
});

Route::prefix('v1/organizer/payments')->middleware('auth:organizer_sanctum')->group(function () {
  Route::get('settings', 'Api\OrganizerPaymentSettingsController@show');
  Route::put('settings', 'Api\OrganizerPaymentSettingsController@update');
});

Route::post('v1/webhooks/razorpay', 'Api\RazorpayWebhookController@handle');