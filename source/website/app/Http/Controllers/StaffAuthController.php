<?php
namespace App\Http\Controllers;
use App\Models\OrganizerStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
class StaffAuthController extends Controller {
 public function login(){return view('staff.login');}
 public function authenticate(Request $r){$d=$r->validate(['organizer_id'=>'required|integer','username'=>'required|string','password'=>'required|string']);$staff=OrganizerStaff::where('organizer_id',$d['organizer_id'])->where('username',$d['username'])->where('active',1)->first();if(!$staff||!Hash::check($d['password'],$staff->password))return back()->withErrors(['username'=>'Invalid credentials.'])->onlyInput('organizer_id','username');Auth::guard('staff')->login($staff);$r->session()->regenerate();$staff->forceFill(['last_login_at'=>now()])->save();return redirect()->route($staff->must_change_password?'staff.password.edit':'staff.home');}
 public function home(){return view('staff.home',['staff'=>auth('staff')->user()]);}
 public function editPassword(){return view('staff.change-password');}
 public function updatePassword(Request $r){$d=$r->validate(['current_password'=>'required','password'=>'required|string|min:8|confirmed']);$s=auth('staff')->user();if(!Hash::check($d['current_password'],$s->password))return back()->withErrors(['current_password'=>'Current password is incorrect.']);$s->update(['password'=>Hash::make($d['password']),'must_change_password'=>false]);return redirect()->route('staff.home');}
 public function logout(Request $r){Auth::guard('staff')->logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('staff.login');}
}