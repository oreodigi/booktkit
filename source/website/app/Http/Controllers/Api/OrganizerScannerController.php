<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event\Booking;
use App\Models\Organizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OrganizerScannerController extends Controller
{
  use \App\Http\Controllers\Concerns\AdmitsThroughUnifiedEngine;


  /* ********************************
     * Submit login for authentication
     * ********************************/
  public function loginSubmit(Request $request)
  {
    $rules = [
      'username' => 'required',
      'password' => 'required',
      'device_name' => 'nullable',
    ];
    $messages = [];
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'status' => 'validation_error',
        'errors' => $validator->errors()
      ], 422);
    }

    // Try to find vendor by username
    $organizer = Organizer::where('username', $request->username)->first();

    if (!$organizer || !Hash::check($request->password, $organizer->password)) {
      return response()->json([
        'success' => false,
        'message' => __('Incorrect username or password')
      ], 422);
    }

    // Get settings safely
    $setting = DB::table('basic_settings')
      ->where('uniqid', 12345)
      ->select('organizer_email_verification', 'organizer_admin_approval')
      ->first();

    // Fallback protection
    if (!$setting) {
      return response()->json([
        'success' => false,
        'message' => __('System configuration not found')
      ], 500);
    }

    // Email verification check
    if (
      $setting->organizer_email_verification == 1 &&
      is_null($organizer->email_verified_at)
    ) {
      return response()->json([
        'success' => false,
        'message' => __('Please verify your email address')
      ], 422);
    }

    // Admin approval check
    if (
      $setting->organizer_admin_approval == 1 &&
      $organizer->status == 0
    ) {
      return response()->json([
        'success' => false,
        'message' => __('Your account is pending admin approval')
      ], 422);
    }

    // Delete old tokens and create new one
    $organizer->tokens()->where('name', $request->device_name ?? 'unknown-device')->delete();

    $token = $organizer->createToken($request->device_name ?? 'unknown-device')->plainTextToken;


    // Add full photo URL if exists
    Auth::guard('organizer_sanctum')->user($organizer);


    $organizer->photo = !empty($organizer->photo) ?  asset('assets/admin/img/organizer-photo/' . $organizer->photo)  : asset('assets/admin/img/blank_user.jpg');

    return response()->json([
      'status' => 'success',
      'organizer' => $organizer,
      'token' => $token
    ], 200);
  }

  //check qr-code
  public function check_qrcode(Request $request)
  {
    // Legacy organizer-app scanner -> unified admission engine.
    return $this->admitLegacyScan($request, 'organizer', (int) Auth::guard('organizer_sanctum')->user()->id);
  }

  public function logoutSubmit(Request $request)
  {
    $request->user()->currentAccessToken()->delete();
    return response()->json([
      'status' => 'success',
      'message' => 'Logout successfully'
    ], 200);
  }


  public function authentication_fail()
  {
    return response()->json([
      'success' => false,
      'message' => 'Unauthenticated.'
    ], 401);
  }
}
