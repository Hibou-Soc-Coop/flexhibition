<?php
namespace App\Helpers;

use App\Models\Language;
use App\Facades\Setting;

class LanguageHelper
{
    public static function getPrimaryLanguage(): Language | null
    {
    $primaryLanguageCode = env('APP_FALLBACK_LOCALE') ?? 'it';
        $primaryLanguage = $primaryLanguageCode
            ? Language::where('code', $primaryLanguageCode)->first()
            : null;
        return $primaryLanguage ?? null;
    }

}
