<?php

namespace App\Models;

use App\Models\BasicSettings\Basic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
  use HasFactory;
  protected $fillable = [
    'text',
    'symbol',
    'text_position',
    'symbol_position',
    'value',
    'is_default',
  ];

  public static function getDefaultCurrency()
  {
    $currency = static::where('is_default', 1)->first();

    if (empty($currency)) {
      $currency = static::latest()->first();
    }

    if (!empty($currency)) {
      return $currency;
    }

    $basicSettings = Basic::query()
      ->select(
        'base_currency_text',
        'base_currency_symbol',
        'base_currency_text_position',
        'base_currency_symbol_position',
        'base_currency_rate'
      )
      ->first();

    if (empty($basicSettings)) {
      return null;
    }

    return new static([
      'text' => $basicSettings->base_currency_text ?? 'USD',
      'symbol' => $basicSettings->base_currency_symbol ?? '$',
      'text_position' => $basicSettings->base_currency_text_position ?? 'left',
      'symbol_position' => $basicSettings->base_currency_symbol_position ?? 'left',
      'value' => $basicSettings->base_currency_rate ?? 1,
      'is_default' => 1,
    ]);
  }

  public static function getSelectedCurrency($currencyId = null)
  {
    if (!empty($currencyId)) {
      $currency = static::find($currencyId);

      if (!empty($currency)) {
        return $currency;
      }
    }

    return static::getDefaultCurrency();
  }
}
