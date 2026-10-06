<?php

namespace App\Http\Controllers;

use App\Jobs\SyncICloudAlbumJob;
use App\Models\ActivityLog;
use App\Models\Album;
use App\Services\ICloud\SharedAlbumClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Admin-side linking of iCloud shared albums.
 *
 * Every import runs on the queue rather than in the request. Two reasons: a
 * first sync of a large album is hundreds of sequential downloads and would
 * blow the request timeout, and the photos directory is owned by www-data so
 * only the worker can write to it.
 */
class ICloudAlbumController extends Controller
{
    public function create(): View
    {
        return view('admin.albums.link', [
            'parentOptions' => Album::query()
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, SharedAlbumClient $client): RedirectResponse
    {
        $validated = $request->validate([
            'link' => ['required', 'string', 'max:512'],
            'parent_id' => ['nullable', 'exists:albums,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $token = SharedAlbumClient::parseToken($validated['link']);

        if ($token === null) {
            return back()->withInput()->withErrors([
                'link' => 'That does not look like an iCloud shared album link.',
            ]);
        }

        if ($existing = Album::where('icloud_token', $token)->first()) {
            return back()->withInput()->withErrors([
                'link' => "That shared album is already linked to \"{$existing->name}\".",
            ]);
        }

        if ($parentId = $validated['parent_id'] ?? null) {
            $parent = Album::findOrFail($parentId);

            if (!$parent->canHaveSubAlbums()) {
                return back()->withInput()->withErrors([
                    'parent_id' => 'Albums only nest two levels deep.',
                ]);
            }
        }

        // Read the album before creating anything, so a bad or revoked link
        // reports the problem here instead of leaving an empty album behind.
        try {
            $fetched = $client->fetchStream($token);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'link' => 'Could not read that shared album. Check the link is still shared publicly. (' . $e->getMessage() . ')',
            ]);
        }

        $stream = $fetched['stream'];
        // Nullable fields are absent from $validated when not submitted, so
        // reach for them defensively rather than by key.
        $name = ($validated['name'] ?? null) ?: ($stream['streamName'] ?? 'Untitled iCloud album');
        $count = count($stream['photos'] ?? []);

        $album = Album::create([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'date_taken' => now()->toDateString(),
            'icloud_token' => $token,
            'icloud_auto_sync' => true,
            'icloud_prune' => false,
            'icloud_sync_status' => 'syncing',
        ]);

        SyncICloudAlbumJob::dispatch($album);

        ActivityLog::log('icloud.linked', "Linked iCloud album \"{$album->name}\"");

        return redirect()
            ->route('admin.albums.index')
            ->with('status', "Linked \"{$name}\" — importing {$count} photo(s) in the background.");
    }

    public function sync(Album $album): RedirectResponse
    {
        if (!$album->icloud_token) {
            return back()->withErrors(['icloud' => 'That album is not linked to iCloud.']);
        }

        $album->update(['icloud_sync_status' => 'syncing']);

        SyncICloudAlbumJob::dispatch($album);

        ActivityLog::log('icloud.sync', "Queued iCloud sync for \"{$album->name}\"");

        return back()->with('status', "Sync queued for \"{$album->name}\".");
    }

    public function update(Request $request, Album $album): RedirectResponse
    {
        $validated = $request->validate([
            'icloud_auto_sync' => ['required', 'boolean'],
        ]);

        $album->update(['icloud_auto_sync' => $validated['icloud_auto_sync']]);

        return back()->with('status', $validated['icloud_auto_sync']
            ? "Hourly sync enabled for \"{$album->name}\"."
            : "Hourly sync paused for \"{$album->name}\".");
    }

    /**
     * Disconnect an album from iCloud without touching its photos — the photos
     * already imported stay exactly as they are, they just stop updating.
     */
    public function destroy(Album $album): RedirectResponse
    {
        $album->update([
            'icloud_token' => null,
            'icloud_stream_ctag' => null,
            'icloud_last_synced_at' => null,
            'icloud_sync_status' => null,
            'icloud_sync_error' => null,
            'icloud_auto_sync' => false,
            'icloud_prune' => false,
        ]);

        ActivityLog::log('icloud.unlinked', "Unlinked iCloud from \"{$album->name}\"");

        return back()->with('status', "\"{$album->name}\" is no longer linked to iCloud. Its photos were kept.");
    }
}
