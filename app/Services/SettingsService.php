<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected $cacheKey = 'app:settings';

    public function all(bool $fresh = false) {
        return Cache::rememberForever($this->cacheKey, function () {
            return Setting::all()->pluck('value', 'key')->toArray();
        });
    }

    public function get(string $key, $default = null) {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    public function set(string $key, $value, $autoload = false) {
        $setting = Setting::updateOrCreate(['key' => $key], [
            'value' => $value,
            'autoload' => $autoload
        ]);
        $this->clearCache();
        return $setting;
    }

    public function clearCache() {
        Cache::forget($this->cacheKey);
    }
}
