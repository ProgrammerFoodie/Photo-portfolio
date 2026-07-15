<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_title' => ['required', 'string', 'max:100'],
            'footer_text' => ['nullable', 'string', 'max:300'],
            'about_title' => ['required', 'string', 'max:100'],
            'about_body' => ['nullable', 'string', 'max:5000'],
            'contact_title' => ['required', 'string', 'max:100'],
            'contact_body' => ['nullable', 'string', 'max:5000'],
            'social_links' => ['nullable', 'array'],
            'social_links.*.label' => ['nullable', 'string', 'max:50'],
            'social_links.*.url' => ['nullable', 'string', 'max:500', 'url'],
            'profile_handle' => ['required', 'string', 'max:50'],
            'profile_display_name' => ['nullable', 'string', 'max:100'],
            'profile_bio' => ['nullable', 'string', 'max:300'],
            'cover_image' => ['nullable', 'image', 'max:8192'],
        ]);

        foreach ($validated as $key => $value) {
            if (in_array($key, ['social_links', 'cover_image'], true)) {
                continue;
            }

            Setting::set($key, $value);
        }

        $socialLinks = collect($validated['social_links'] ?? [])
            ->filter(fn ($row) => filled($row['label'] ?? null) && filled($row['url'] ?? null))
            ->map(fn ($row) => ['label' => $row['label'], 'url' => $row['url']])
            ->values()
            ->all();

        Setting::set('social_links', json_encode($socialLinks));

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
