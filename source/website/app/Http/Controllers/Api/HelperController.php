<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class HelperController extends Controller
{
  public static function getAppLanguage($request)
  {
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first() : Language::where('is_default', 1)->first();
    return $language;
  }
  public static function getImagePath($path, $image)
  {
    if (!empty($image)) {
      return asset("$path$image");
    }
    return null;
  }
}
