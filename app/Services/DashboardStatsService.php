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
            $totalSizeBytes = (int) Photo::sum('filesize');

            return [
                'total_photos' => Photo::count(),
                'total_size_bytes' => $totalSizeBytes,
                'total_size_human' => Number::fileSize($totalSizeBytes, precision: 2),
                'total_albums' => Album::count(),
                'total_top_level_albums' => Album::whereNull('parent_id')->count(),
                'total_sub_albums' => Album::whereNotNull('parent_id')->count(),
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