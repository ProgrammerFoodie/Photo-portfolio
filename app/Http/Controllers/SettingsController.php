<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    private const COVER_DIR = 'profile';

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => Setting::allCached(),
            'socialLinks' => Setting::socialLinks(),
            'homeServices' => Setting::homeServices(),
            'homeProcessSteps' => Setting::homeProcessSteps(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_title' => ['nullable', 'string', 'max:100'],
            'footer_text' => ['nullable', 'string', 'max:300'],
            'about_title' => ['nullable', 'string', 'max:100'],
            'about_body' => ['nullable', 'string', 'max:5000'],
            'contact_title' => ['nullable', 'string', 'max:100'],
            'contact_body' => ['nullable', 'string', 'max:5000'],
            'social_links' => ['nullable', 'array'],
            'social_links.*.label' => ['nullable', 'string', 'max:50'],
            'social_links.*.url' => ['nullable', 'string', 'max:500', 'url'],
            'profile_handle' => ['nullable', 'string', 'max:50'],
            'profile_display_name' => ['nullable', 'string', 'max:100'],
            'profile_bio' => ['nullable', 'string', 'max:300'],
            'cover_image' => ['nullable', 'image', 'max:8192'],
            'profile_cover_position_y' => ['nullable', 'integer', 'min:0', 'max:100'],
            'profile_header_height' => ['nullable', 'integer', 'min:120', 'max:800'],
            'theme' => ['required', 'string', Rule::in(array_keys(config('themes')))],
            'home_services' => ['nullable', 'array'],
            'home_services.*' => ['nullable', 'string', 'max:40'],
            'home_process_steps' => ['nullable', 'array'],
            'home_process_steps.*.title' => ['nullable', 'string', 'max:40'],
            'home_process_steps.*.body' => ['nullable', 'string', 'max:400'],
            'home_cta_heading' => ['nullable', 'string', 'max:120'],
            'home_cta_subtext' => ['nullable', 'string', 'max:200'],
        ]);

        $previousTheme = Setting::get('theme');

        foreach ($validated as $key => $value) {
            if (in_array($key, ['social_links', 'cover_image', 'home_services', 'home_process_steps'], true)) {
                continue;
            }

            Setting::set($key, $value);
        }

        if ($previousTheme !== $validated['theme']) {
            $themeLabel = config('themes')[$validated['theme']] ?? $validated['theme'];
            ActivityLog::log('theme.changed', "Changed theme to \"{$themeLabel}\"");
        } else {
            ActivityLog::log('settings.updated', 'Updated site settings');
        }

        $socialLinks = collect($validated['social_links'] ?? [])
            ->filter(fn ($row) => filled($row['label'] ?? null) && filled($row['url'] ?? null))
            ->map(fn ($row) => ['label' => $row['label'], 'url' => $row['url']])
            ->values()
            ->all();

        Setting::set('social_links', json_encode($socialLinks));

        $homeServices = collect($validated['home_services'] ?? [])
            ->filter(fn ($item) => filled($item))
            ->values()
            ->all();

        Setting::set('home_services', json_encode($homeServices));

        $homeProcessSteps = collect($validated['home_process_steps'] ?? [])
            ->filter(fn ($row) => filled($row['title'] ?? null) || filled($row['body'] ?? null))
            ->map(fn ($row) => ['title' => $row['title'] ?? '', 'body' => $row['body'] ?? ''])
            ->values()
            ->all();

        Setting::set('home_process_steps', json_encode($homeProcessSteps));

        if ($request->hasFile('cover_image')) {
            $disk = Storage::disk('local');
            $disk->makeDirectory(self::COVER_DIR);

            $oldPath = Setting::get('profile_cover_path');
            if ($oldPath && $disk->exists($oldPath)) {
                $disk->delete($oldPath);
            }

            $extension = $request->file('cover_image')->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            $newPath = $disk->putFileAs(self::COVER_DIR, $request->file('cover_image'), $filename);

            Setting::set('profile_cover_path', $newPath);
            Setting::set('profile_cover_position_y', '50');
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Settings saved.');
    }

    /**
     * The cover image lives on the private "local" disk (same reasoning as
     * photo thumbnails: Storage::url() requires a signed URL for private
     * disks), so it's served through this route instead.
     */
    public function coverImage(): StreamedResponse
    {
        $path = Setting::get('profile_cover_path');

        abort_unless($path, 404);

        return Storage::disk('local')->response($path);
    }
}
