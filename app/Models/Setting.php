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
            'home_services' => json_encode(['Weddings', 'Portraits', 'Families', 'Editorial', 'Everyday Light']),
            'home_process_steps' => json_encode([
                ['title' => 'Inquire', 'body' => "Reach out with your date, your people, your vision — I'll reply within a day or two with availability and a straightforward quote."],
                ['title' => 'Shoot', 'body' => "On the day, I stay light on direction and heavy on attention — real moments over posed ones, but I'll guide the shots that need guiding."],
                ['title' => 'Select', 'body' => "Every keeper gets a full edit pass. You'll get a private online gallery to review and share."],
                ['title' => 'Delivery', 'body' => 'High-resolution files, yours to keep — no watermarks, no expiring links.'],
            ]),
            'home_cta_heading' => "Let's make something worth keeping",
            'home_cta_subtext' => 'Weddings, portraits, and everyday light — booking now for upcoming dates.',
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

    /**
     * Home page (version-2 "contact sheet" design) services ticker items,
     * stored as a JSON array of strings in the `home_services` setting.
     */
    public static function homeServices(): array
    {
        $decoded = json_decode(static::get('home_services', '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Home page (version-2 "contact sheet" design) four-step process list,
     * stored as a JSON array of {title, body} pairs in the
     * `home_process_steps` setting.
     */
    public static function homeProcessSteps(): array
    {
        $decoded = json_decode(static::get('home_process_steps', '[]'), true);

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
