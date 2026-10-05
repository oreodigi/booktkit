<?php
namespace App\Http\Controllers\BackEnd;
use App\Http\Controllers\Controller;use App\Models\BoxOfficeSetting;use Illuminate\Http\Request;
class AdminBoxOfficeSettingsController extends Controller{
 public function edit(){return view('backend.box-office.settings',['setting'=>BoxOfficeSetting::firstOrCreate(['organizer_id'=>null,'event_id'=>null])]);}
 public function update(Request $r){$d=$r->validate(['hold_minutes'=>'required|integer|min:1|max:120','receipt_width_mm'=>'required|in:58,80']);foreach(['allow_cash','allow_upi','allow_card','allow_other','require_customer_name','require_customer_phone','allow_aadhaar','allow_customer_photo','auto_email_ticket'] as $k)$d[$k]=$r->boolean($k);BoxOfficeSetting::updateOrCreate(['organizer_id'=>null,'event_id'=>null],$d);return back()->with('success','Platform POS defaults updated.');}
}