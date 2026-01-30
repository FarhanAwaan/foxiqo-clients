<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key', 'value', 'type', 'description', 'is_sensitive',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

    public static function getValue(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            $value = $setting->value;

            if ($setting->is_sensitive && $value) {
                $value = decrypt($value);
            }

            return match ($setting->type) {
                'integer' => (int) $value,
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($value, true),
                default => $value,
            };
        });
    }

    public static function setValue(string $key, $value, string $type = 'string', bool $sensitive = false): void
    {
        $setting = static::where('key', $key)->first();

        if ($sensitive && $value) {
            $value = encrypt($value);
        }

        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            static::create([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'is_sensitive' => $sensitive,
            ]);
        }

        Cache::forget("setting.{$key}");
    }
}
