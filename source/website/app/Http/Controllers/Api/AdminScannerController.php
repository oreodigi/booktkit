<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Event\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminScannerController extends Controller
{
  use \App\Http\Controllers\Concerns\AdmitsThroughUnifiedEngine;

  /* ********************************
     * Submit login for authentication
     * ********************************/
  public function loginSubmit(Request $request)
  {
    $rules = [
      'username' => 'required',
      'password' => 'required'
    ];
    $messages = [];
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'status' => 'validation_error',
        'errors' => $validator->errors()
      ], 422);
    }

    // Attempt login manually using credentials
    $admin = Admin::where('username', $request->username)->first();

    if (!$admin || !Hash::check($request->password, $admin->password)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Invalid credentials'
      ], 401);
    }

    if ($admin->status == 0) {
      return response()->json([
        'status' => 'error',
        'message' => 'Sorry, your account has been deactivated.'
      ], 403);
    }

    // Delete old tokens and create new one
    $admin->tokens()->where('name', $request->device_name ?? 'unknown-device')->delete();
    $token = $admin->createToken($request->device_name ?? 'unknown-device')->plainTextToken;

 
    $admin->image = !empty($admin->image) ?  asset('assets/admin/img/admins/' . $admin->image)  : asset('assets/admin/img/blank_user.jpg');

    Auth::guard('admin_sanctum')->user($admin);
    return response()->json([
      'status' => 'success',
      'admin' => $admin,
      'token' => $token
    ], 200);
  }
  public function check_qrcode(Request $request)
  {
    // Legacy admin-app scanner -> unified admission engine.
    return $this->admitLegacyScan($request, 'admin', (int) Auth::guard('admin_sanctum')->user()->id);
  }

  //check qr code
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
