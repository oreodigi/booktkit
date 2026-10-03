<?php

namespace App\Http\Controllers\Api\Organizer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Organizer;
use App\Models\Transaction;
use App\Models\Withdraw;
use App\Models\WithdrawMethodInput;
use App\Models\WithdrawPaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class WithdrawController extends Controller
{
  public function index()
  {
    $withdraws = Withdraw::with('method')
      ->where('organizer_id', Auth::guard('organizer_sanctum')->user()->id)
      ->orderby('id', 'desc')
      ->get();
    $information['balance'] = Auth::guard('organizer_sanctum')->user()->amount;
    $information['withdraws']  = $withdraws;
    $information['getCurrencyInfo']  = $this->getCurrencyInfo();

    return response()->json(['success' => true, 'data' => $information], 200);
  }
  //create
  public function create()
  {
    $information = [];
    $methods = WithdrawPaymentMethod::where('status', '=', 1)->get();
    $information['methods'] = $methods;

    return response()->json(['success' => true, 'data' => $information], 200);
  }

  //get_inputs
  public function getInputs($id)
  {
    $data = WithdrawMethodInput::with('options')->where('withdraw_payment_method_id', $id)->orderBy('order_number', 'asc')->get();

    return response()->json(['success' => true, 'data' => $data], 200);
  }

  //balance_calculation
  public function balanceCalculation($methodId, $amount)
  {
    $method = WithdrawPaymentMethod::where('id', $methodId)->first();
    if (!$method) {
      return response()->json(['success' => false, 'message' => "Method Not Found!"], 200);
    }
    $fixed_charge = $method->fixed_charge;
    $percentage = $method->percentage_charge;

    $percentage_balance = ($amount * $percentage) / 100;
    $total_charge = $percentage_balance + $fixed_charge;
    $receive_balance = $amount - $total_charge;

    $user_balance = Auth::guard('organizer_sanctum')->user()->amount - $amount;

    return ['total_charge' => round($total_charge, 2), 'receive_balance' => round($receive_balance, 2), 'user_balance' => round($user_balance, 2)];
  }

  //send_request
  //send_request
  public function sendRequest(Request $request)
  {
    $method = WithdrawPaymentMethod::where('id', $request->withdraw_method)->first();

    $organizer = Organizer::where('id', Auth::guard('organizer_sanctum')->user()->id)->first();


    $bs = DB::table('basic_settings')->select('base_currency_symbol', 'base_currency_symbol_position')->first();
    $leftPosition = $bs->base_currency_symbol_position == 'left' ? $bs->base_currency_symbol : '';
    $rightPosition = $bs->base_currency_symbol_position == 'right' ? $bs->base_currency_symbol : '';

    if (!$request->withdraw_method) {
      return response()->json(['errors' => ['withdraw_method' => [__('Withdraw Method field is required.')]]], 400);
    } elseif (intval($request->withdraw_amount) < $method->min_limit) {
      return response()->json(['errors' => ['withdraw_amount' => [__('Minimum withdraw limit is') . ' ' . $leftPosition . $method->min_limit . $rightPosition]]], 400);
    } elseif (intval($request->withdraw_amount) > $method->max_limit) {
      return response()->json(['errors' => ['withdraw_amount' => [__('Maximum withdraw limit is') . ' ' . $leftPosition . $method->max_limit . $rightPosition]]], 400);
    }

    $rules = [
      'withdraw_method' => 'required',
      'withdraw_amount' => "required",
    ];
    $inputs = WithdrawMethodInput::where('withdraw_payment_method_id', $request->withdraw_method)->orderBy('order_number', 'asc')->get();

    foreach ($inputs as $input) {
      if ($input->required == 1) {
        $rules["$input->name"] = 'required';
      }

      $fields = [];
      foreach ($inputs as $key => $input) {
        $in_name = $input->name;
        if ($request["$in_name"]) {
          $fields["$in_name"] = $request["$in_name"];
        }
      }
      $jsonfields = json_encode($fields);
      $jsonfields = str_replace("\/", "/", $jsonfields);;
    }

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return Response::json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }

    //show error if current amount is less then withdraw amount
    if ($organizer->amount < $request->withdraw_amount) {
      return response()->json([
        'succcess' => false,
        'message' => __("You don't have enough amount to withdraw!")
      ], 400);
    }

    //calculation

    $fixed_charge = $method->fixed_charge;
    $percentage = $method->percentage_charge;

    $percentage_balance = ($request->withdraw_amount * $percentage) / 100;
    $total_charge = $percentage_balance + $fixed_charge;
    $receive_balance = $request->withdraw_amount - $total_charge;
    //calculation end

    $save = new Withdraw;
    $save->withdraw_id = uniqid();
    $save->organizer_id = Auth::guard('organizer_sanctum')->user()->id;
    $save->method_id = $request->withdraw_method;

    $organizer = Organizer::where('id', Auth::guard('organizer_sanctum')->user()->id)->first();
    $pre_balance = $organizer->amount;
    $organizer->amount = ($organizer->amount - ($request->withdraw_amount));
    $organizer->save();
    $after_balance = $organizer->amount;

    $save->amount = $request->withdraw_amount;
    $save->payable_amount = $receive_balance;
    $save->total_charge = $total_charge;
    $save->additional_reference = $request->additional_reference;
    $save->feilds = json_encode($fields);
    $save->save();

    //store data to transcation table
    $currencyInfo = $this->getCurrencyInfo();
    $transcation = Transaction::create([
      'transcation_id' => time(),
      'booking_id' => $save->id,
      'transcation_type' => 3,
      'user_id' => null,
      'organizer_id' => Auth::guard('organizer_sanctum')->user()->id,
      'payment_status' => 0,
      'payment_method' => $save->method_id,
      'grand_total' => $save->amount,
      'pre_balance' => $pre_balance,
      'after_balance' => $after_balance,
      'gateway_type' => null,
      'currency_symbol' => $currencyInfo->base_currency_symbol,
      'currency_symbol_position' => $currencyInfo->base_currency_text_position,
    ]);

    return response()->json([
      'succcess' => true,
      'message' => __("Withdraw Request Send Successfully!")
    ], 200);
  }
  //delete
  public function delete(Request $request)
  {
    $withdraw = Withdraw::where([['organizer_id', Auth::guard('organizer_sanctum')->user()->id], ['id', $request->id]])->first();
    if (!$withdraw) {
      return response()->json(['success' => false, 'message' => __("Withdraw Request not found!")], 404);
    }
    if ($withdraw->status == 0) {
      $organizer = Organizer::find(Auth::guard('organizer_sanctum')->user()->id);
      $organizer->amount = ($organizer->amount + ($withdraw->amount));
      $organizer->save();
    }
    $withdraw->delete();

    return response()->json(['success' => true, 'message' => __("Withdraw Request Deleted Successfully!")], 200);
  }

  //bulkDelete
  public function bulkDelete(Request $request)
  {
    $ids = $request->ids;
    if (empty($ids)) {
      return response()->json(['success' => false, 'message' => __("No Withdraw Request selected!")], 400);
    }
    foreach ($ids as $id) {
      $withdraw = Withdraw::where([['organizer_id', Auth::guard('organizer_sanctum')->user()->id], ['id', $id]])->first();
      if (!$withdraw) {
        return response()->json(['success' => false, 'message' => __("Withdraw Request not found!")], 404);
      }
      if ($withdraw->status == 0) {
        $organizer = Organizer::find(Auth::guard('organizer_sanctum')->user()->id);
        $organizer->amount = ($organizer->amount + ($withdraw->amount));
        $organizer->save();
      }
      $withdraw->delete();
    }
    return response()->json(['success' => true, 'message' => __("Withdraw Request Deleted Successfully!")], 200);
  }
}
