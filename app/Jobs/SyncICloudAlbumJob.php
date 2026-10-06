<?php

namespace App\Jobs;

use App\Models\Album;
use App\Services\ICloud\SharedAlbumSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Syncing is unique per album: the hourly schedule and a manual run must never
 * import the same album concurrently, or both would download the same photos
 * before either had written its rows.
 */
class SyncICloudAlbumJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    // Generous: a first sync of a large album downloads every photo serially.
    public int $timeout = 900;

    public function __construct(public Album $album)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->album->id;
    }

    /**
     * Stop the lock outliving a hung run by more than one scheduled cycle.
     */
    public function uniqueFor(): int
    {
        return 3600;
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(SharedAlbumSyncService $sync): void
    {
        $sync->sync($this->album);
    }
}
