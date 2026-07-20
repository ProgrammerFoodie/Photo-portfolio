<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'site.settings';

    /** Code-level defaults; DB rows override these. */
    public static function defaults(): array
    {
        return [
            'site_title' => config('app.name', 'Gallery'),
            'footer_text' => '© ' . now()->year . ' ' . config('app.name', 'Gallery'),
            'about_title' => 'About',
            'about_body' => '',
            'contact_title' => 'Contact',
            'contact_body' => '',
            'social_links' => '[]',
            'profile_handle' => config('app.name', 'Gallery'),
            'profile_display_name' => '',
            'profile_bio' => '',
            'profile_cover_path' => '',
            'profile_cover_position_y' => '50',
            'profile_header_height' => '280',
            'theme' => 'default',
        ];
    }

    /**
     * Admin-defined list of {label, url} pairs (socials, WhatsApp click-to-chat
     * links, etc.) stored as JSON in the `social_links` setting.
     */
    public static function socialLinks(): array
    {
        $decoded = json_decode(static::get('social_links', '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return array_merge(
                static::defaults(),
                static::query()->pluck('value', 'key')->all(),
            );
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::allCached()[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }
}
