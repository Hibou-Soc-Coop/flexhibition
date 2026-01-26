<?php
namespace App\Helpers;

use App\Models\Language;
use App\Models\Setting;

class LanguageHelper
{
    public static function getPrimaryLanguage(): Language | null
    {
        $primaryLanguageCode = Setting::where('key', 'primary_language')->value('value') ?? env('APP_FALLBACK_LOCALE');
        $primaryLanguage = $primaryLanguageCode
            ? Language::where('code', $primaryLanguageCode)->first()
            : null;
        return $primaryLanguage ?? null;
    }

}
