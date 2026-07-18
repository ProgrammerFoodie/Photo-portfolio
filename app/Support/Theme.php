<?php

namespace App\Support;

use App\Models\Setting;

class Theme
{
    public static function current(): string
    {
        return Setting::get('theme', 'default');
    }

    public static function is(string $slug): bool
    {
        return static::current() === $slug;
    }
}
