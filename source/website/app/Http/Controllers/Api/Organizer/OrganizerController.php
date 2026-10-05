<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Api\HelperController;
use Config;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BasicSettings\Basic;
use App\Models\BasicSettings\MailTemplate;
use App\Models\BasicSettings\PageHeading;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Mail\Message;
use App\Models\SupportTicket;
use Illuminate\Validation\Rule;
use App\Models\Event;
use App\Models\Event\Booking;
use App\Models\Organizer;
use App\Models\OrganizerInfo;
use App\Models\Services\Services;
use App\Models\Staff\StaffGlobalDay;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\Services\ServiceBooking;
use App\Models\Transaction;
use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\PHPMailer;

class OrganizerController extends Controller

{
  private $admin_user_name;
  public function __construct()
  {
    $admin = Admin::select('username')->first();
    $this->admin_user_name = $admin->username;
  }
  //signup
  public function signup(Request $request)
  {
    //get language
    $language = HelperController::getAppLanguage($request);

    $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('organizer_signup_page_title')->first();

    $basic_settings = Basic::query()->select('google_recaptcha_status', 'google_recaptcha_site_key', 'google_recaptcha_secret_key', 'facebook_login_status', 'facebook_app_id', 'facebook_app_secret', 'google_login_status', 'google_client_id', 'google_client_secret', 'breadcrumb')->first();
    $basic_settings['breadcrumb'] = asset('assets/admin/img/' . $basic_settings->breadcrumb);

    $data['bs'] = $basic_settings;
    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  public function signupSubmit(Request $request)
  {
    $rules = [
      'name' => 'required',
      'username' => [
        'required',
        'alpha_dash',
        'unique:organizers',
        "not_in:$this->admin_user_name"
      ],
      'email' => 'required|email|unique:organizers',
      'password' => 'required|confirmed|min:6',
    ];

    $basic = Basic::select('google_recaptcha_status')->first();

    if ($basic->google_recaptcha_status == 1) {
      $rules['g-recaptcha-response'] = 'required|captcha';
    }

    $messages = [];
    if ($basic->google_recaptcha_status == 1) {
      $messages = [
        'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
        'g-recaptcha-response.captcha' => 'Captcha error! try again later.',
      ];
    }

    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    $setting = DB::table('basic_settings')
      ->where('uniqid', 12345)
      ->select('organizer_email_verification', 'organizer_admin_approval')
      ->first();

    $data = $request->only(['name', 'username', 'email']);
    $data['password'] = Hash::make($request->password);

    /** ================= EMAIL VERIFICATION ================= */
    if ($setting->organizer_email_verification == 1) {

      $token =  $request->email;
      $data['email_verification_token'] = $token;
      $data['email_verified_at'] = null;

      $mailTemplate = MailTemplate::where('mail_type', 'verify_email')->first();
      $smtp = DB::table('basic_settings')->first();

      $verifyLink = url("organizers/email/verify?token=" . $token);

      $mailBody = str_replace(
        ['{username}', '{verification_link}', '{website_title}'],
        [$request->username, '<a href="' . $verifyLink . '">Click Here</a>', $smtp->website_title],
        $mailTemplate->mail_body
      );

      try {
        $mail = new \App\Support\EnvironmentMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp->smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp->smtp_username;
        $mail->Password   = $smtp->smtp_password;
        $mail->Port       = $smtp->smtp_port;

        if ($smtp->encryption == 'TLS') {
          $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom($smtp->from_mail, $smtp->from_name);
        $mail->addAddress($request->email);
        $mail->isHTML(true);
        $mail->Subject = $mailTemplate->mail_subject;
        $mail->Body    = $mailBody;
        $mail->send();
      } catch (\Exception $e) {
        return response()->json([
          'success' => false,
          'message' => 'Email sending failed'
        ], 500);
      }
    } else {
      $data['email_verified_at'] = now();
    }

    /** ================= STATUS ================= */
    if ($setting->organizer_admin_approval == 1) {
      $data['status'] = 0;
    } else {
      $data['status'] = 1;
    }

    /** ================= SAVE ================= */
    $organizer = Organizer::create($data);

    OrganizerInfo::create([
      'organizer_id' => $organizer->id,
      'language_id'  => $this->getLanguage()->id,
    ]);

    return response()->json([
      'success' => true,
      'message' => __('Sign up completed successfully. Please check your email.')
    ]);
  }

  //login
  public function login(Request $request)
  {
    //get language
    $language = HelperController::getAppLanguage($request);

    $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('organizer_login_page_title')->first();

    $data['bgImg'] = asset('assets/img/' . @$this->getBreadcrumb()->breadcrumb);

    $data['bs'] = Basic::query()->select('google_recaptcha_status', 'facebook_login_status', 'google_login_status')->first();

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }


  public function dashboard()
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;

    $information['balance'] = Auth::guard('organizer_sanctum')->user()->amount;
    $information['getCurrencyInfo']  = $this->getCurrencyInfo();

    $information['total_events'] = Event::where('organizer_id', $organizer_id)->get()->count();
    $information['total_event_bookings'] = Booking::where('organizer_id', $organizer_id)->get()->count();
    $information['transcation_count'] = Transaction::where('organizer_id', $organizer_id)->get()->count();

    //income of event bookings
    $eventBookingTotalIncomes = DB::table('bookings')
      ->select(DB::raw('month(created_at) as month'), DB::raw('sum(price) as total'))
      ->where('paymentStatus', '=', 'completed')
      ->groupBy('month')
      ->whereYear('created_at', '=', date('Y'))
      ->where('organizer_id', $organizer_id)
      ->get();

    $TotalEventBookings = DB::table('bookings')
      ->select(DB::raw('month(created_at) as month'), DB::raw('count(id) as total'))
      ->where('paymentStatus', '=', 'completed')
      ->groupBy('month')
      ->whereYear('created_at', '=', date('Y'))
      ->where('organizer_id', $organizer_id)
      ->get();



    $eventMonths = [];

    $eventIncomes = [];
    $totalBookings = [];

    //event icome calculation
    for ($i = 1; $i <= 12; $i++) {
      // get all 12 months name
      $monthNum = $i;
      $dateObj = DateTime::createFromFormat('!m', $monthNum);
      $monthName = $dateObj->format('M');
      array_push($eventMonths, $monthName);

      // get all 12 months's income
      $incomeFound = false;

      foreach ($eventBookingTotalIncomes as $eventIncomeInfo) {
        if ($eventIncomeInfo->month == $i) {
          $incomeFound = true;
          array_push($eventIncomes, $eventIncomeInfo->total);
          break;
        }
      }

      if ($incomeFound == false) {
        array_push($eventIncomes, 0);
      }


      // get all 12 months's c
      $bookingFound = false;

      foreach ($TotalEventBookings as $eventInfo) {
        if ($eventInfo->month == $i) {
          $bookingFound = true;
          array_push($totalBookings, $eventInfo->total);
          break;
        }
      }
      if ($bookingFound == false) {
        array_push($totalBookings, 0);
      }
    }
    $information['eventIncomes'] = $eventIncomes;
    $information['eventMonths'] = $eventMonths;
    $information['totalBookings'] = $totalBookings;

    $information['admin_setting'] = DB::table('basic_settings')->where('uniqid', 12345)->select('organizer_admin_approval', 'admin_approval_notice')->first();

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }
  //monthly  income
  public function monthlyIncome(Request $request)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    if ($request->filled('year')) {
      $date = $request->input('year');
    } else {
      $date = date('Y');
    }

    $monthWiseTotalIncomes = DB::table('transactions')->where('organizer_id',  $organizer_id)
      ->select(DB::raw('month(created_at) as month'), DB::raw('sum(grand_total) as total'))
      ->where(function ($query) {
        return $query->where('transcation_type', 1)
          ->orWhere('transcation_type', 4);
      })
      ->where('payment_status', 1)
      ->groupBy('month')
      ->whereYear('created_at', '=', $date)
      ->get();


    $monthWiseTotalReject = DB::table('transactions')->where('organizer_id',  $organizer_id)
      ->select(DB::raw('month(created_at) as month'), DB::raw('sum(grand_total) as total'))
      ->where('transcation_type', 3)
      ->where('payment_status', 2)
      ->groupBy('month')
      ->whereYear('created_at', '=', $date)
      ->get();


    $monthWiseTotalCommission = DB::table('transactions')->where('organizer_id',  $organizer_id)
      ->select(DB::raw('month(created_at) as month'), DB::raw('sum(commission) as total'))
      ->where(function ($query) {
        return $query->where('transcation_type', 1)
          ->orWhere('transcation_type', 3);
      })
      ->where('payment_status', 1)
      ->groupBy('month')
      ->whereYear('created_at', '=', $date)
      ->get();

    $monthWiseTotalExpenses = DB::table('transactions')->where('organizer_id',  $organizer_id)
      ->select(DB::raw('month(created_at) as month'), DB::raw('sum(grand_total) as total'))
      ->where(function ($query) {
        return $query->where('transcation_type', 3)
          ->orWhere('transcation_type', 5);
      })
      ->groupBy('month')
      ->whereYear('created_at', '=', $date)
      ->get();

    $months = [];
    $incomes = [];
    $rejects = [];
    $commissions = [];
    $expenses = [];
    for ($i = 1; $i <= 12; $i++) {
      // get all 12 months name
      $monthNum = $i;
      $dateObj = DateTime::createFromFormat('!m', $monthNum);
      $monthName = $dateObj->format('M');
      array_push($months, $monthName);

      // get all 12 months's income of booking
      $incomeFound = false;
      foreach ($monthWiseTotalIncomes as $incomeInfo) {
        if ($incomeInfo->month == $i) {
          $incomeFound = true;
          array_push($incomes, $incomeInfo->total);
          break;
        }
      }
      if ($incomeFound == false) {
        array_push($incomes, 0);
      }

      // get all 12 months's total reject
      $rejectFound = false;
      foreach ($monthWiseTotalReject as $Reject) {
        if ($Reject->month == $i) {
          $rejectFound = true;
          array_push($rejects, $Reject->total);
          break;
        }
      }
      if ($rejectFound == false) {
        array_push($rejects, 0);
      }

      // get all 12 months's commission of event booking
      $commissionFound = false;
      foreach ($monthWiseTotalCommission as $commissionInfo) {
        if ($commissionInfo->month == $i) {
          $commissionFound = true;
          array_push($commissions, $commissionInfo->total);
          break;
        }
      }
      if ($commissionFound == false) {
        array_push($commissions, 0);
      }

      // get all 12 months's expenses of equipment booking
      $expensesFound = false;
      foreach ($monthWiseTotalExpenses as $expensesInfo) {
        if ($expensesInfo->month == $i) {
          $expensesFound = true;
          array_push($expenses, $expensesInfo->total);
          break;
        }
      }
      if ($expensesFound == false) {
        array_push($expenses, 0);
      }
    }
    $information['months'] = $months;
    $information['incomes'] = $incomes;
    $information['rejects'] = $rejects;
    $information['commissions'] = $commissions;
    $information['expenses'] = $expenses;

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }


  public function updatedPassword(Request $request)
  {
    $rules = [
      'current_password' => 'required',
      'new_password' => 'required|confirmed',
    ];

    $messages = [
      'new_password.confirmed' => __('Password confirmation does not match.')
    ];

    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()->toArray()
      ], 400);
    }

    $organizer = Auth::guard('organizer_sanctum')->user();

    // Check if current password is correct
    if (!Hash::check($request->current_password, $organizer->password)) {
      return response()->json([
        'success' => false,
        'errors' => [
          'current_password' => [__('The current password is incorrect.')]
        ]
      ], 422);
    }

    // Check if new password is same as current password
    if ($request->new_password === $request->current_password) {
      return response()->json([
        'success' => false,
        'errors' => [
          'new_password' => [__('New password cannot be the same as the current password.')]
        ]
      ], 422);
    }

    // Update the password
    $organizer->update([
      'password' => Hash::make($request->new_password)
    ]);

    return response()->json([
      'success' => true,
      'message' => __('Password updated successfully!')
    ]);
  }
  //edit_profile
  public function editProfile()
  {
    $information['languages'] = Language::get();
    $organizer = Auth::guard('organizer_sanctum')->user();

    if ($organizer->photo) {
      $organizer->photo = HelperController::getImagePath('assets/admin/img/organizer-photo/', $organizer->photo);
    }

    $information['organizer'] = $organizer;
    $information['organizer_infos'] = OrganizerInfo::Where('organizer_id', $organizer->id)->get();

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  public function updateProfile(Request $request)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $rules = [
      'email' => [
        'required',
        Rule::unique('organizers', 'email')->ignore($organizer_id)
      ],
      'username' => [
        'required',
        'alpha_dash',
        "not_in:$this->admin_user_name",
        Rule::unique('organizers', 'username')->ignore($organizer_id)
      ],
    ];
    $languages = Language::get();

    $messages = [];

    foreach ($languages as $language) {
      $rules[$language->code . '_name'] = 'required';
      $messages[$language->code . '_name'] = 'The name field is required for ' . $language->name . ' language.';
    }

    if ($request->hasFile('photo')) {
      $rules['photo']  = 'dimensions:width=300,height=300|mimes:jpg,jpeg,png';
    }
    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
      return Response::json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }
    $in = $request->all();
    $organizer = Organizer::find($organizer_id);
    $file = $request->file('photo');
    if ($file) {
      $extension = $file->getClientOriginalExtension();
      $directory = public_path('assets/admin/img/organizer-photo/');
      $fileName = uniqid() . '.' . $extension;
      @mkdir($directory, 0775, true);
      $file->move($directory, $fileName);

      @unlink(public_path('assets/admin/img/organizer-photo/') . $organizer->photo);
      $in['photo'] = $fileName;
    }
    $organizer->update($in);

    $languages = Language::get();
    foreach ($languages as $language) {
      $organizer_info = OrganizerInfo::where('organizer_id', $organizer->id)->where('language_id', $language->id)->first();
      if (!$organizer_info) {
        $organizer_info = new OrganizerInfo();
        $organizer_info->language_id = $language->id;
        $organizer_info->organizer_id = $organizer->id;
      }
      $organizer_info->name = $request[$language->code . '_name'];
      $organizer_info->designation = $request[$language->code . '_designation'];
      $organizer_info->country = $request[$language->code . '_country'];
      $organizer_info->city = $request[$language->code . '_city'];
      $organizer_info->state = $request[$language->code . '_state'];
      $organizer_info->zip_code = $request[$language->code . '_zip_code'];
      $organizer_info->address = $request[$language->code . '_address'];
      $organizer_info->details = $request[$language->code . '_details'];
      $organizer_info->save();
    }

    return response()->json([
      'success' => true,
      'message' => __('Profile updated successfully!')
    ]);
  }

  //transaction
  public function transcation(Request $request)
  {
    $transcation_id = null;
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    if ($request->filled('transcation_id')) {
      $transcation_id = $request->transcation_id;
    }
    $data['transactions'] = Transaction::where('organizer_id', $organizer_id)
      ->when($transcation_id, function ($query) use ($transcation_id) {
        return $query->where('transcation_id', 'like', '%' . $transcation_id . '%');
      })
      ->orderBy('id', 'desc')->paginate(10);

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }
}
