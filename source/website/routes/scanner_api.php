<?php

use App\Http\Controllers\ScannerApi\AdminScannerController;
use App\Http\Controllers\ScannerApi\BasicController;
use App\Http\Controllers\ScannerApi\OrganizerScannerController;
use App\Http\Controllers\ScannerApi\StaffScannerController;
use Illuminate\Support\Facades\Route;

Route::prefix('/scanner')->group(function () {
  Route::get('/get-basic', [BasicController::class, 'getBasic'])->name('api.getBasic');
  Route::get('/get-lang/{code}', [BasicController::class, 'getLang'])->name('api.getLang');

  Route::prefix('/organizer')->group(function () {
    Route::post('/login/submit', [OrganizerScannerController::class, 'loginSubmit'])->name('api.scanner.organizer.login_submit');
    Route::get('/authentication-fail', [OrganizerScannerController::class, 'authentication_fail'])->name('api.scanner.organizer.authentication.fail');
    Route::middleware('auth:organizer_sanctum')->group(function () {
      Route::get('/gates', [OrganizerScannerController::class, 'gates'])->name('api.scanner.organizer.gates');
      Route::get('/events', [OrganizerScannerController::class, 'events'])->name('api.scanner.organizer.events');
      Route::post('/ticket/scanned-status-change', [OrganizerScannerController::class, 'ticketScanStatusChanged'])->name('api.scanner.organizer.scanned_status_change');
      Route::post('/check-qrcode', [OrganizerScannerController::class, 'check_qrcode'])->name('api.scanner.organizer.check-qrcode');
      Route::post('/logout', [OrganizerScannerController::class, 'logoutSubmit'])->name('api.scanner.organizer.logout');
    });
  });
  Route::prefix('/admin')->group(function () {
    Route::post('/login/submit', [AdminScannerController::class, 'loginSubmit'])->name('api.scanner.admin.login_submit');
    Route::get('/authentication-fail', [AdminScannerController::class, 'authentication_fail'])->name('api.scanner.admin.authentication.fail');
    Route::group(['middleware' => 'auth:admin_sanctum'], function ($e) {
      Route::get('/gates', [AdminScannerController::class, 'gates'])->name('api.scanner.admin.gates');
      Route::get('/events', [AdminScannerController::class, 'events'])->name('api.scanner.admin.events');
      Route::post('/ticket/scanned-status-change', [AdminScannerController::class, 'ticketScanStatusChanged'])->name('api.scanner.admin.scanned_status_change');
      Route::post('/check-qrcode', [AdminScannerController::class, 'check_qrcode'])->name('api.scanner.admin.check-qrcode');
      Route::post('/logout', [AdminScannerController::class, 'logoutSubmit'])->name('api.scanner.admin.logout');
    });
  });
  Route::prefix('/staff')->group(function () {
    Route::post('/login/submit', [StaffScannerController::class, 'login'])->name('api.scanner.staff.login');
    Route::middleware('auth:staff_sanctum')->group(function () {
      Route::get('/gates', [StaffScannerController::class, 'gates'])->name('api.scanner.staff.gates');
      Route::get('/events', [StaffScannerController::class, 'events'])->name('api.scanner.staff.events');
      Route::post('/check-qrcode', [StaffScannerController::class, 'scan'])->name('api.scanner.staff.check-qrcode');
      Route::post('/logout', [StaffScannerController::class, 'logout'])->name('api.scanner.staff.logout');
    });
  });
});
