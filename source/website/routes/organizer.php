<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackEnd\Organizer\OrganizerPayoutKycController;

Route::prefix('staff')->group(function () {
  Route::middleware('guest:staff')->group(function () {
    Route::get('login','StaffAuthController@login')->name('staff.login');
    Route::post('login','StaffAuthController@authenticate')->middleware('throttle:staff-login')->name('staff.authenticate');
  });
  Route::middleware(['auth:staff','staff.assignment'])->group(function () {
    Route::get('/','StaffAuthController@home')->name('staff.home');
    Route::get('shifts','StaffShiftController@index')->middleware('staff.assignment:box_office.sell')->name('staff.shifts.index');
    Route::post('shifts/open','StaffShiftController@open')->middleware('staff.assignment:box_office.sell')->name('staff.shifts.open');
    Route::post('shifts/{id}/close','StaffShiftController@close')->middleware('staff.assignment:box_office.sell')->name('staff.shifts.close');
    Route::get('box-office','StaffBoxOfficeController@index')->middleware('staff.assignment:box_office.sell')->name('staff.boxoffice.index');
    Route::post('box-office/sales','StaffBoxOfficeController@store')->middleware('staff.assignment:box_office.sell')->name('staff.boxoffice.store');
    Route::get('box-office/sales/{id}/print','StaffBoxOfficeController@print')->middleware('staff.assignment:box_office.sell')->name('staff.boxoffice.print');
    Route::post('box-office/sales/{id}/reprint','StaffBoxOfficeController@reprint')->middleware('staff.assignment:box_office.reprint')->name('staff.boxoffice.reprint');
    Route::get('change-password','StaffAuthController@editPassword')->name('staff.password.edit');
    Route::post('change-password','StaffAuthController@updatePassword')->name('staff.password.update');
    Route::post('logout','StaffAuthController@logout')->name('staff.logout');
  });
});

/*
|--------------------------------------------------------------------------
| User Interface Routes
|--------------------------------------------------------------------------
*/

Route::get('organizer/pwa/', 'BackEnd\Organizer\OrganizerController@pwa')->name('organizer.pwa');
Route::post('organizer/check-qrcode/', 'BackEnd\Organizer\OrganizerController@check_qrcode')->name('check-qrcode');

Route::post('/ai/generate/content', 'BackEnd\Organizer\AiContentController@generateContent')
  ->middleware(['auth:organizer', 'organizer.ai.system', 'organizer.ai.quota'])
  ->name('organizer.ai.generate.content');
Route::post('/organizer/ai/generate-slider-images', 'BackEnd\Organizer\AiImageController@generateSliderImages')
  ->middleware(['auth:organizer', 'organizer.ai.system', 'organizer.ai.quota'])
  ->name('organizer.ai.generate.slider.images');
  Route::post('/ai/generate/image', 'BackEnd\Organizer\AiImageController@generateImage')
  ->middleware(['auth:organizer', 'organizer.ai.system', 'organizer.ai.quota'])
  ->name('organizer.ai.generate.category.image');

Route::get('organizers/email/verify', 'BackEnd\Organizer\OrganizerController@confirm_email');

Route::prefix('/organizer')->group(function () {
  Route::middleware('guest:organizer', 'change.lang', 'adminLang')->group(function () {
    Route::get('/login', 'BackEnd\Organizer\OrganizerController@login')->name('organizer.login');
    Route::get('/signup', 'BackEnd\Organizer\OrganizerController@signup')->name('organizer.signup');
    Route::post('/create', 'BackEnd\Organizer\OrganizerController@create')->name('organizer.create');
    Route::post('/store', 'BackEnd\Organizer\OrganizerController@authentication')->name('organizer.authentication');
    Route::get('/forget-password', 'BackEnd\Organizer\OrganizerController@forget_passord')->name('organizer.forget.password');
    Route::post('/send-forget-mail', 'BackEnd\Organizer\OrganizerController@forget_mail')->name('organizer.forget.mail');
    Route::get('/reset-password', 'BackEnd\Organizer\OrganizerController@reset_password')->name('organizer.reset.password');
    Route::post('/update-forget-password', 'BackEnd\Organizer\OrganizerController@update_password')->name('organizer.update-forget-password');
  });

  Route::get('/logout', 'BackEnd\Organizer\OrganizerController@logout')->name('organizer.logout');
  Route::get('/change-password', 'BackEnd\Organizer\OrganizerController@change_password')->name('organizer.change.password');
  Route::post('/update-password', 'BackEnd\Organizer\OrganizerController@updated_password')->name('organizer.update_password');
});

Route::prefix('/organizer/ai-token-purchase')->group(function () {
  Route::get('paypal/notify', 'BackEnd\Organizer\PaymentGateway\PaypalController@notify')->name('organizer.ai_token_purchase.paypal.notify');
  Route::get('paystack/notify', 'BackEnd\Organizer\PaymentGateway\PaystackController@notify')->name('organizer.ai_token_purchase.paystack.notify');
  Route::get('instamojo/notify', 'BackEnd\Organizer\PaymentGateway\InstamojoController@notify')->name('organizer.ai_token_purchase.instamojo.notify');
  Route::post('razorpay/notify', 'BackEnd\Organizer\PaymentGateway\RazorpayController@notify')->name('organizer.ai_token_purchase.razorpay.notify');
  Route::post('mercadopago/notify', 'BackEnd\Organizer\PaymentGateway\MercadoPagoController@notify')->name('organizer.ai_token_purchase.mercadopago.notify');
  Route::get('mollie/notify', 'BackEnd\Organizer\PaymentGateway\MollieController@notify')->name('organizer.ai_token_purchase.mollie.notify');
  Route::post('paytm/notify', 'BackEnd\Organizer\PaymentGateway\PaytmController@notify')->name('organizer.ai_token_purchase.paytm.notify');
  Route::post('flutterwave/notify', 'BackEnd\Organizer\PaymentGateway\FlutterwaveController@notify')->name('organizer.ai_token_purchase.flutterwave.notify');
  Route::get('midtrans/notify/{orderId}', 'BackEnd\Organizer\PaymentGateway\MidtransController@ccNotify')->name('organizer.ai_token_purchase.midtrans.notify');
  Route::post('paytabs/notify', 'BackEnd\Organizer\PaymentGateway\PaytabsController@notify')->name('organizer.ai_token_purchase.paytabs.notify');
  Route::get('toyyibpay/notify', 'BackEnd\Organizer\PaymentGateway\ToyyibpayController@notify')->name('organizer.ai_token_purchase.toyyibpay.notify');
  Route::any('phonepe/notify', 'BackEnd\Organizer\PaymentGateway\PhonepeController@notify')->name('organizer.ai_token_purchase.phonepe.notify');
  Route::get('yoco/notify', 'BackEnd\Organizer\PaymentGateway\YocoController@notify')->name('organizer.ai_token_purchase.yoco.notify');
  Route::get('xendit/notify', 'BackEnd\Organizer\PaymentGateway\XenditController@notify')->name('organizer.ai_token_purchase.xendit.notify');
  Route::post('iyzico/notify', 'BackEnd\Organizer\PaymentGateway\IyzipayController@notify')->name('organizer.ai_token_purchase.iyzico.notify');
  Route::get('perfect-money/notify', 'BackEnd\Organizer\PaymentGateway\PerfectMoneyController@notify')->name('organizer.ai_token_purchase.perfect-money.notify');
  Route::get('perfect-money/cancel', 'BackEnd\Organizer\PaymentGateway\PerfectMoneyController@cancel')->name('organizer.ai_token_purchase.perfect-money.cancel');
});

Route::prefix('/organizer')->middleware('auth:organizer', 'Deactive:organizer', 'EmailStatus:organizer', 'adminLang', 'organizer.staff.rbac')->group(function () {
  Route::get('/events/{eventId}/passes', 'BackEnd\\Organizer\\EventPassController@index')->name('organizer.event.passes.index');
  Route::post('/events/{eventId}/passes', 'BackEnd\\Organizer\\EventPassController@store')->name('organizer.event.passes.store');
  Route::put('/events/{eventId}/passes/{passId}', 'BackEnd\\Organizer\\EventPassController@update')->name('organizer.event.passes.update');
  Route::delete('/events/{eventId}/passes/{passId}', 'BackEnd\\Organizer\\EventPassController@destroy')->name('organizer.event.passes.destroy');
  Route::get('/access-control', 'BackEnd\\Organizer\\AccessCredentialController@index')->name('organizer.access.index');
  Route::put('/access-control/events/{eventId}/policy', 'BackEnd\\Organizer\\AccessCredentialController@savePolicy')->name('organizer.access.policy');
  Route::post('/access-control/batches', 'BackEnd\\Organizer\\AccessCredentialController@createBatch')->name('organizer.access.batch');
  Route::post('/access-control/assign', 'BackEnd\\Organizer\\AccessCredentialController@assign')->name('organizer.access.assign');
  Route::post('/access-control/replace', 'BackEnd\\Organizer\\AccessCredentialController@replace')->name('organizer.access.replace');
  Route::post('/access-control/zones', 'BackEnd\\Organizer\\AccessCredentialController@createZone')->name('organizer.access.zone');
  Route::post('/access-control/gates', 'BackEnd\\Organizer\\AccessCredentialController@createGate')->name('organizer.access.gate');
  Route::get('/box-office/reports', 'BackEnd\\Organizer\\BoxOfficeReportController@index')->name('organizer.boxoffice.reports.index');
  Route::get('/box-office/shifts', 'BackEnd\\Organizer\\BoxOfficeShiftController@index')->name('organizer.boxoffice.shifts.index');
  Route::post('/box-office/shifts/{id}/verify', 'BackEnd\\Organizer\\BoxOfficeShiftController@verify')->name('organizer.boxoffice.shifts.verify');
  Route::get('/box-office', 'BackEnd\\Organizer\\BoxOfficeController@index')->name('organizer.boxoffice.index');
  Route::get('/box-office/holds', 'BackEnd\\Organizer\\BoxOfficeOperationsController@holds')->name('organizer.boxoffice.holds.index');
  Route::post('/box-office/holds', 'BackEnd\\Organizer\\BoxOfficeOperationsController@hold')->name('organizer.boxoffice.holds.store');
  Route::post('/box-office/holds/{id}/resume', 'BackEnd\\Organizer\\BoxOfficeOperationsController@resume')->name('organizer.boxoffice.holds.resume');
  Route::delete('/box-office/holds/{id}', 'BackEnd\\Organizer\\BoxOfficeOperationsController@destroy')->name('organizer.boxoffice.holds.destroy');
  Route::get('/box-office/settings', 'BackEnd\\Organizer\\BoxOfficeOperationsController@settings')->name('organizer.boxoffice.settings');
  Route::put('/box-office/settings', 'BackEnd\\Organizer\\BoxOfficeOperationsController@updateSettings')->name('organizer.boxoffice.settings.update');
  Route::post('/box-office/sales', 'BackEnd\\Organizer\\BoxOfficeController@store')->name('organizer.boxoffice.store');
  Route::get('/box-office/sales/{id}/print', 'BackEnd\\Organizer\\BoxOfficeController@print')->name('organizer.boxoffice.print');
  Route::post('/box-office/sales/{id}/reprint', 'BackEnd\\Organizer\\BoxOfficeController@reprint')->name('organizer.boxoffice.reprint');
  Route::post('/box-office/sales/{id}/void-request', 'BackEnd\\Organizer\\BoxOfficeController@requestVoid')->name('organizer.boxoffice.void.request');
  Route::post('/box-office/sales/{id}/void-approve', 'BackEnd\\Organizer\\BoxOfficeController@approveVoid')->name('organizer.boxoffice.void.approve');
  Route::get('/team', 'BackEnd\\Organizer\\StaffController@index')->name('organizer.staff.index');
  Route::post('/team', 'BackEnd\\Organizer\\StaffController@store')->name('organizer.staff.store');
  Route::put('/team/{id}', 'BackEnd\\Organizer\\StaffController@update')->name('organizer.staff.update');
  Route::post('/team/{id}/reset-password', 'BackEnd\\Organizer\\StaffController@resetPassword')->name('organizer.staff.reset_password');
  Route::delete('/team/{id}', 'BackEnd\\Organizer\\StaffController@destroy')->name('organizer.staff.destroy');
  Route::get('/dashboard', 'BackEnd\Organizer\OrganizerController@index')->name('organizer.dashboard');
  Route::get('/payouts-kyc', [OrganizerPayoutKycController::class, 'edit'])->name('organizer.payouts.kyc');
  Route::post('/payouts-kyc', [OrganizerPayoutKycController::class, 'save'])->name('organizer.payouts.kyc.save');
  Route::get('monthly-income', 'BackEnd\Organizer\OrganizerController@monthly_income')->name('organizer.monthly_income');
  Route::get('/transaction', 'BackEnd\Organizer\OrganizerController@transaction')->name('organizer.transcation');
  Route::post('/transcation/delete', 'BackEnd\Organizer\OrganizerController@destroy')->name('organizer.transcation.delete');
  Route::post('/transcation/bulk-delete', 'BackEnd\Organizer\OrganizerController@bulk_destroy')->name('organizer.transcation.bulk_delete');

  // change admin-panel theme (dark/light) route
  Route::post('/change-theme', 'BackEnd\Organizer\OrganizerController@changeTheme')->name('organizer.change_theme');

  Route::get('/edit-profile', 'BackEnd\Organizer\OrganizerController@edit_profile')->name('organizer.edit.profile');
  Route::post('/organizer-update-profile', 'BackEnd\Organizer\OrganizerController@update_profile')->name('organizer.update_profile');

  Route::get('/verify/email', 'BackEnd\Organizer\OrganizerController@verify_email')->name('organizer.verify.email');
  Route::post('/send-verify/link', 'BackEnd\Organizer\OrganizerController@send_link')->name('organizer.send.verify.link');
  Route::get('/email/verify', 'BackEnd\Organizer\OrganizerController@confirm_email');

  Route::get('event-management/events/', 'BackEnd\Organizer\EventController@index')->name('organizer.event_management.event');
  Route::get('choose-event-type/', 'BackEnd\Organizer\EventController@choose_event_type')->name('choose-event-type');
  Route::get('add-event/', 'BackEnd\Organizer\EventController@add_event')->name('organizer.add.event.event');
  Route::post('event-imagesstore', 'BackEnd\Organizer\EventController@gallerystore')->name('organizer.event.imagesstore');
  Route::post('event-imagermv', 'BackEnd\Organizer\EventController@imagermv')->name('organizer.event.imagermv');
  Route::post('event-store', 'BackEnd\Organizer\EventController@store')->name('organizer.event_management.store_event');
  Route::post('/event/{id}/update-status', 'BackEnd\Organizer\EventController@updateStatus')->name('organizer.event_management.event.event_status');
  Route::post('/event/{id}/update-featured', 'BackEnd\Organizer\EventController@updateFeatured')->name('organizer.event_management.event.update_featured');
  Route::post('/duplicate-event/{id}', 'BackEnd\Organizer\EventController@duplicate')->name('organizer.event_management.duplicate_event');
  Route::post('/delete-event/{id}', 'BackEnd\Organizer\EventController@destroy')->name('organizer.event_management.delete_event');
  Route::get('/edit-event/{id}', 'BackEnd\Organizer\EventController@edit')->name('organizer.event_management.edit_event');
  Route::post('/event-img-dbrmv', 'BackEnd\Organizer\EventController@imagedbrmv')->name('organizer.event.imgdbrmv');


  Route::get('all-country', 'BackEnd\Organizer\EventController@getCountry')->name('organizer.get_country');
  Route::get('all-state', 'BackEnd\Organizer\EventController@searchSate')->name('organizer.get_state');
  Route::get('all-city', 'BackEnd\Organizer\EventController@getSearchCity')->name('organizer.get_city');

  Route::get('get-state/', 'BackEnd\Organizer\EventController@get_state')->name('organizer.get.city.state');
  Route::get('get-cities/', 'BackEnd\Organizer\EventController@getcities')->name('organizer.get.cities.state');

  // seat mapping
  Route::prefix('/seat-mapping')->group(function () {
    //toggle option slot
    Route::post('/toggle-option-slot', 'BackEnd\Organizer\SlotSeatController@slotAction')->name('organizer.event_management.seat_mapping.action');
    //slot
    Route::prefix('/slot')->group(function ($e) {
      Route::get('all/event/{event}/ticket/{ticket}/slot-unique/{slot_unique_id}', 'BackEnd\Organizer\SlotSeatController@allSlot')->name('organizer.event_management.seat_mapping');
      Route::post('/background-image-update', 'BackEnd\Organizer\SlotSeatController@storeBackgroundImage')->name('organizer.event_management.seat_mapping.store_ticket');
      Route::post('/update-store', 'BackEnd\Organizer\SlotSeatController@slotStoreUpdate')->name('organizer.event_management.seat_mapping.slot.store_update');
      Route::post('/drag-drop', 'BackEnd\Organizer\SlotSeatController@slotDrupDrop')->name('organizer.event_management.seat_mapping.slot.drup_drop');
      Route::post('/delete', 'BackEnd\Organizer\SlotSeatController@slotDelete')->name('organizer.event_management.seat_mapping.slot.delete');

      Route::prefix('/seats')->group(function ($e) {
        Route::get('/', 'BackEnd\Organizer\SlotSeatController@slotSeat')->name('organizer.event_management.seat_mapping.slot.seat_mapping');
        Route::post('/update', 'BackEnd\Organizer\SlotSeatController@slotSeatUpdate')->name('organizer.event_management.seat_mapping.slot.seat_mapping_update');
      });
    });
  });

  //ticket settings
  Route::get('/edit-ticket-setting/{id}', 'BackEnd\Organizer\EventController@editTicketSetting')->name('organizer.event_management.ticket_setting');
  Route::post('/update-ticket-setting', 'BackEnd\Organizer\EventController@updateTicketSetting')->name('organizer.event_management.update_ticket_setting');

  Route::get('/event-images/{id}', 'BackEnd\Organizer\EventController@images')->name('organizer.event.images');
  Route::post('/event-update', 'BackEnd\Organizer\EventController@update')->name('organizer.event.update');
  Route::post('bulk/delete/event', 'BackEnd\Organizer\EventController@bulk_delete')->name('organizer.event_management.bulk_delete_event');


  Route::get('event/ticket', 'BackEnd\Organizer\TicketController@index')->name('organizer.event.ticket');
  Route::get('event/add-ticket', 'BackEnd\Organizer\TicketController@create')->name('organizer.event.add.ticket');
  Route::post('event/ticket/store-ticket', 'BackEnd\Organizer\TicketController@store')->name('organizer.ticket_management.store_ticket');
  Route::get('event/edit/ticket', 'BackEnd\Organizer\TicketController@edit')->name('organizer.event.edit.ticket');
  Route::post('event/ticket/delete-ticket', 'BackEnd\Organizer\TicketController@destroy')->name('organizer.ticket_management.delete_ticket');
  Route::get('delete-variation/{id}', 'BackEnd\Organizer\TicketController@delete_variation')->name('organizer.delete.variation');
  Route::post('ticket_management/update/ticket', 'BackEnd\Organizer\TicketController@update')->name('organizer.ticket_management.update_ticket');
  Route::post('bulk/delete/bulk/event/ticket', 'BackEnd\Organizer\TicketController@bulk_delete')->name('organizer.event_management.bulk_delete_event_ticket');

  Route::get('withdraw', 'BackEnd\Organizer\OrganizerWithdrawController@index')->name('organizer.withdraw');
  Route::get('withdraw/create', 'BackEnd\Organizer\OrganizerWithdrawController@create')->name('organizer.withdraw.create');
  Route::get('/get-withdraw-method/input/{id}', 'BackEnd\Organizer\OrganizerWithdrawController@get_inputs');

  Route::get('withdraw/balance-calculation/{method}/{amount}', 'BackEnd\Organizer\OrganizerWithdrawController@balance_calculation');

  Route::post('/withdraw/send-request', 'BackEnd\Organizer\OrganizerWithdrawController@send_request')->name('organizer.withdraw.send-request');
  Route::post('/withdraw/witdraw/bulk-delete', 'BackEnd\Organizer\OrganizerWithdrawController@bulkDelete')->name('organizer.witdraw.bulk_delete_withdraw');
  Route::post('/withdraw/witdraw/delete', 'BackEnd\Organizer\OrganizerWithdrawController@Delete')->name('organizer.witdraw.delete_withdraw');

  Route::get('event-booking', 'BackEnd\Organizer\EventBookingController@index')->name('organizer.event.booking');
  Route::post('event-booking/update/payment-status/{id}', 'BackEnd\Organizer\EventBookingController@updatePaymentStatus')->name('organizer.event_booking.update_payment_status');
  Route::get('event-booking/details/{id}', 'BackEnd\Organizer\EventBookingController@show')->name('organizer.event_booking.details');
  Route::post('/{id}/delete', 'BackEnd\Organizer\EventBookingController@destroy')->name('organizer.event_booking.delete');
  Route::post('/event-booking/bulk-delete', 'BackEnd\Organizer\EventBookingController@bulkDestroy')->name('organizer.event_booking.bulk_delete');
  Route::get('/event-booking/report', 'BackEnd\Organizer\EventBookingController@report')->name('organizer.event_booking.report');
  Route::get('/event-booking/export', 'BackEnd\Organizer\EventBookingController@export')->name('organizer.event_bookings.export');


  /*
  |---------------------------------------------
  |support ticket
  |---------------------------------------------
  */


  Route::prefix('support-tikcet')->group(function () {
    Route::get('create', 'BackEnd\Organizer\SupportTicketController@create')->name('organizer.support_ticket.create');
    Route::post('/store', 'BackEnd\Organizer\SupportTicketController@store')->name('organizer.support_ticket.store');
    Route::get('tickets', 'BackEnd\Organizer\SupportTicketController@index')->name('organizer.support_tickets');
    Route::get('/message/{id}', 'BackEnd\Organizer\SupportTicketController@message')->name('organizer.support_tickets.message');
    Route::post('/zip-upload', 'BackEnd\Organizer\SupportTicketController@zip_file_upload')->name('organizer.support_ticket.zip_file.upload');
    Route::post('/reply/{id}', 'BackEnd\Organizer\SupportTicketController@ticketreply')->name('organizer.support_ticket.reply');

    Route::post('/delete/{id}', 'BackEnd\Organizer\SupportTicketController@delete')->name('organizer.support_tickets.delete');
    Route::post('/bulk/delete/', 'BackEnd\Organizer\SupportTicketController@bulk_delete')->name('organizer.support_tickets.bulk_delete');
  });

  Route::prefix('ai-token-purchase')->middleware('organizer.ai.system')->group(function () {
    Route::get('packages', 'BackEnd\Organizer\AiTokenPurchaseController@packages')->name('organizer.ai_token_purchase.packages');
    Route::post('start-checkout/{id}', 'BackEnd\Organizer\AiTokenPurchaseController@startCheckout')->name('organizer.ai_token_purchase.start_checkout');
    Route::get('checkout', 'BackEnd\Organizer\AiTokenPurchaseController@checkout')->name('organizer.ai_token_purchase.checkout');
    Route::post('pay', 'BackEnd\Organizer\AiTokenPurchaseController@pay')->name('organizer.ai_token_purchase.pay');
    Route::get('history', 'BackEnd\Organizer\AiTokenPurchaseController@history')->name('organizer.ai_token_purchase.history');
    Route::get('details/{id}', 'BackEnd\Organizer\AiTokenPurchaseController@details')->name('organizer.ai_token_purchase.details');
    Route::get('complete', 'BackEnd\Organizer\AiTokenPurchaseController@complete')->name('organizer.ai_token_purchase.complete');
    Route::get('cancel', 'BackEnd\Organizer\AiTokenPurchaseController@cancel')->name('organizer.ai_token_purchase.cancel');
  });
});

Route::prefix('/organizer')->middleware(['auth:organizer'])->group(function () {
  Route::get('/payments-settlements', 'BackEnd\Organizer\PaymentCenterController@index')->name('organizer.payments.index');
  Route::post('/payments-settlements/preference', 'BackEnd\Organizer\PaymentCenterController@preference')->name('organizer.payments.preference');
});