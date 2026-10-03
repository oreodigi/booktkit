<?php

namespace App\Http\Controllers\BackEnd;

use App\Models\Currency;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class CurrencyController extends Controller
{
  public function index()
  {
    if (!Currency::exists()) {
      Currency::create([
        'text' => 'USD',
        'symbol' => '$',
        'text_position' => 'right',
        'symbol_position' => 'left',
        'value' => 1,
        'is_default' => 1,
      ]);
    }

    $data['currencies'] = Currency::latest()->get();

    $defaultCurrency = Currency::where('is_default', 1)->first();
    if (empty($defaultCurrency)) {
      $defaultCurrency = Currency::first();
      if (!empty($defaultCurrency)) {
        $defaultCurrency->update(['is_default' => 1]);
      }
    }

    $data['default_currency'] = $defaultCurrency;
    return view('backend.currencies.index', $data);
  }

  public function store(Request $request)
  {
    $request->validate([
      'text' => 'required|string|max:255',
      'symbol' => 'required|string|max:255',
      'text_position' => 'required|in:left,right',
      'symbol_position' => 'required|in:left,right',
      'value' => 'required|numeric|min:0',
    ]);

    Currency::create([
      'text' => $request->text,
      'symbol' => $request->symbol,
      'text_position' => $request->text_position,
      'symbol_position' => $request->symbol_position,
      'value' => $request->value,
      'is_default' => 0,
    ]);

    Session::flash('success', __('Added Successfully') . '.');

    return response()->json(['status' => 'success'], 200);
  }

  public function update(Request $request)
  {
    $request->validate([
      'id' => 'required|exists:currencies,id',
      'text' => 'required|string|max:255',
      'symbol' => 'required|string|max:255',
      'text_position' => 'required|in:left,right',
      'symbol_position' => 'required|in:left,right',
      'value' => 'required|numeric|min:0',
    ]);

    $currency = Currency::find($request->id);

    $currency->update([
      'text' => $request->text,
      'symbol' => $request->symbol,
      'text_position' => $request->text_position,
      'symbol_position' => $request->symbol_position,
      'value' => $request->value,
    ]);

    Session::flash('success', __('Updated Successfully') . '.');

    return response()->json(['status' => 'success'], 200);
  }

  public function makeDefault($id)
  {
    Currency::where('is_default', 1)->update(['is_default' => 0]);

    $currency = Currency::findOrFail($id);
    $currency->update(['is_default' => 1]);

    return back()->with('success', $currency->text . ' ' . __('is set as default currency') . '.');
  }

  public function destroy(Request $request)
  {
    $request->validate([
      'currency_id' => 'required|exists:currencies,id',
    ]);

    $currency = Currency::findOrFail($request->currency_id);

    if ((int) $currency->is_default === 1) {
      return back()->with('warning', __('Default currency cannot be deleted') . '.');
    }

    if (Currency::count() <= 1) {
      return back()->with('warning', __('At least one currency must remain') . '.');
    }

    $currency->delete();

    return back()->with('success', __('Deleted Successfully') . '.');
  }
}
