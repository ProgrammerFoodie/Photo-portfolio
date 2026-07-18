<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Download;
use App\Models\Photo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

class DashboardStatsService
{
    private const CACHE_KEY = 'admin.dashboard.stats';

    /**
     * Aggregate counts for the dashboard overview cards.
     * Cached briefly since these run a handful of full-table aggregates.
     */
    public function getOverview(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            $diskPath = storage_path();
            $diskTotalBytes = (int) disk_total_space($diskPath);
            $diskFreeBytes = (int) disk_free_space($diskPath);
            $diskUsedBytes = $diskTotalBytes - $diskFreeBytes;

            // Only plain scalars go into this array, never Eloquent models —
            // this gets serialized into the database cache table, and
            // unserializing model objects/relations back out of a text
            // column is unreliable (breaks into __PHP_Incomplete_Class).
            $topAlbum = Album::query()
                ->withCount('downloads')
                ->having('downloads_count', '>', 0)
                ->orderByDesc('downloads_count')
                ->first(['id', 'slug', 'name']);

            $topPhoto = Photo::query()
                ->with('album:id,slug')
                ->withCount('downloads')
                ->having('downloads_count', '>', 0)
                ->orderByDesc('downloads_count')
                ->first(['id', 'album_id', 'original_filename']);

            return [
                'total_photos' => Photo::count(),
                'disk_used_human' => Number::fileSize($diskUsedBytes, precision: 1),
                'disk_total_human' => Number::fileSize($diskTotalBytes, precision: 1),
                'total_albums' => Album::count(),
                'total_downloads' => Download::count(),
                'uploads_this_week' => Photo::where('created_at', '>=', now()->subDays(7))->count(),
                'top_album_slug' => $topAlbum?->slug,
                'top_album_name' => $topAlbum?->name,
                'top_album_downloads' => $topAlbum?->downloads_count,
                'top_photo_id' => $topPhoto?->id,
                'top_photo_album_slug' => $topPhoto?->album?->slug,
                'top_photo_name' => $topPhoto?->original_filename,
                'top_photo_downloads' => $topPhoto?->downloads_count,
            ];
        });
    }

    /**
     * Call this after any create/update/delete that would change the numbers
     * above, so the dashboard doesn't show stale data for up to 5 minutes.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}