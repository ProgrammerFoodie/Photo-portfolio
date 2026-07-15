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

            return [
                'total_photos' => Photo::count(),
                'disk_used_human' => Number::fileSize($diskUsedBytes, precision: 1),
                'disk_total_human' => Number::fileSize($diskTotalBytes, precision: 1),
                'total_albums' => Album::count(),
                'total_downloads' => Download::count(),
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