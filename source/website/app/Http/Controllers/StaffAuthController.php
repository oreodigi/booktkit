<?php
namespace App\Http\Controllers;
use App\Models\OrganizerStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
class StaffAuthController extends Controller {
 public function login(){return redirect()->route('organizer.login');}
 public function authenticate(Request $r){
  $d=$r->validate(['organizer_id'=>'nullable|integer','username'=>'required|string','password'=>'required|string']);
  $q=OrganizerStaff::with('organizer')->where('active',1)->where(function($x)use($d){$x->where('username',$d['username'])->orWhere('email',$d['username']);});
  if(!empty($d['organizer_id']))$q->where('organizer_id',$d['organizer_id']);
  $matches=$q->get()->filter(fn($s)=>Hash::check($d['password'],$s->password));
  if($matches->count()!==1)return back()->withErrors(['username'=>'Invalid team credentials.'])->onlyInput('username');
  $staff=$matches->first();
  if(!$staff->organizer||!$staff->organizer->status)return back()->withErrors(['username'=>'This organizer account is not active.'])->onlyInput('username');
  Auth::guard('staff')->login($staff);
  Auth::guard('organizer')->login($staff->organizer);
  $r->session()->regenerate();
  $staff->forceFill(['last_login_at'=>now()])->save();
  return redirect()->route($staff->must_change_password?'staff.password.edit':'staff.home');
 }
 public function home(){return view('staff.home',['staff'=>auth('staff')->user(),'permissions'=>config('staff.permissions')]);}
 public function editPassword(){return view('staff.change-password');}
 public function updatePassword(Request $r){$d=$r->validate(['current_password'=>'required','password'=>'required|string|min:8|confirmed']);$s=auth('staff')->user();if(!Hash::check($d['current_password'],$s->password))return back()->withErrors(['current_password'=>'Current password is incorrect.']);$s->update(['password'=>Hash::make($d['password']),'must_change_password'=>false]);return redirect()->route('staff.home');}
 public function logout(Request $r){Auth::guard('staff')->logout();Auth::guard('organizer')->logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('organizer.login');}
}