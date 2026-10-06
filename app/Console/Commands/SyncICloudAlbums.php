<?php

namespace App\Console\Commands;

use App\Jobs\SyncICloudAlbumJob;
use App\Models\Album;
use App\Services\ICloud\SharedAlbumSyncService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class SyncICloudAlbums extends Command
{
    protected $signature = 'icloud:sync
        {album? : Album ID. Omit to sync every album with auto-sync on}
        {--now : Run the import in this process instead of queueing it}
        {--force : Ignore the stored ctag and re-check even if nothing changed}
        {--dry-run : Report what would happen without downloading or writing}';

    protected $description = 'Sync linked iCloud shared albums into the gallery';

    public function handle(SharedAlbumSyncService $sync): int
    {
        $albums = $this->albums();

        if ($albums->isEmpty()) {
            $this->comment('No linked iCloud albums to sync.');

            return self::SUCCESS;
        }

        // Photos are written to a directory owned by www-data (mode 0700), and
        // the scheduler's crontab belongs to admin — so importing in-process
        // from cron would fail on every file write. Queueing hands the work to
        // the worker, which runs as www-data. --now exists for debugging and
        // must be run as the right user to succeed.
        if (!$this->option('now') && !$this->option('dry-run')) {
            return $this->queue($albums);
        }

        return $this->runInline($sync, $albums);
    }

    /**
     * @param  Collection<int, Album>  $albums
     */
    private function queue(Collection $albums): int
    {
        foreach ($albums as $album) {
            SyncICloudAlbumJob::dispatch($album);
            $this->line("Queued #{$album->id} {$album->name}");
        }

        $this->info("Queued {$albums->count()} album(s) for sync.");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Album>  $albums
     */
    private function runInline(SharedAlbumSyncService $sync, Collection $albums): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->comment('Dry run — nothing will be downloaded, written or deleted.');
        }

        $failures = 0;

        foreach ($albums as $album) {
            // --force clears the stored ctag in memory only, so the album is
            // re-read from Apple even when it reports as unchanged.
            if ($this->option('force')) {
                $album->icloud_stream_ctag = null;
            }

            $this->line("Syncing #{$album->id} {$album->name}...");

            $result = $sync->sync($album, $dryRun);

            if ($result->error) {
                $this->error("  {$result->summary()}");
                $failures++;
                continue;
            }

            $this->info("  {$result->summary()}");
        }

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return Collection<int, Album>
     */
    private function albums(): Collection
    {
        if ($id = $this->argument('album')) {
            return Album::whereKey($id)->whereNotNull('icloud_token')->get();
        }

        return Album::whereNotNull('icloud_token')
            ->where('icloud_auto_sync', true)
            ->orderBy('id')
            ->get();
    }
}
