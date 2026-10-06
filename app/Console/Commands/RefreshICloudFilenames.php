<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Services\ICloud\SharedAlbumClient;
use App\Services\ICloud\SharedAlbumSyncService;
use Illuminate\Console\Command;

/**
 * Backfills real camera filenames onto photos that were imported before the
 * sync learned to read them out of Apple's asset URLs.
 *
 * Only the asset URLs are re-resolved — nothing is downloaded again, so this is
 * cheap and safe to re-run.
 */
class RefreshICloudFilenames extends Command
{
    protected $signature = 'icloud:refresh-filenames
        {album? : Album ID. Omit for every linked album}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Backfill original camera filenames on already-imported iCloud photos';

    public function handle(SharedAlbumClient $client, SharedAlbumSyncService $sync): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $albums = $this->argument('album')
            ? Album::whereKey($this->argument('album'))->whereNotNull('icloud_token')->get()
            : Album::whereNotNull('icloud_token')->orderBy('id')->get();

        if ($albums->isEmpty()) {
            $this->comment('No linked iCloud albums.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->comment('Dry run — nothing will be written.');
        }

        $changed = 0;
        $unchanged = 0;
        $unresolved = 0;

        foreach ($albums as $album) {
            $this->line("Album #{$album->id} {$album->name}");

            try {
                // Pass no ctag: we always want the current photo list, even
                // when nothing has changed since the last sync.
                $fetched = $client->fetchStream($album->icloud_token);
            } catch (\Throwable $e) {
                $this->error("  Could not read album: {$e->getMessage()}");

                continue;
            }

            if ($fetched === null) {
                continue;
            }

            $photos = $album->photos()
                ->whereNotNull('icloud_photo_guid')
                ->whereNotNull('icloud_checksum')
                ->get();

            foreach ($photos->chunk(SharedAlbumClient::ASSET_BATCH_SIZE) as $chunk) {
                $urls = $client->fetchAssetUrls(
                    $fetched['base'],
                    $chunk->pluck('icloud_photo_guid')->all(),
                );

                foreach ($chunk as $photo) {
                    $url = $urls[$photo->icloud_checksum] ?? null;
                    $name = $url ? $sync->filenameFromUrl($url) : null;

                    if ($name === null) {
                        $unresolved++;
                        continue;
                    }

                    if ($name === $photo->original_filename) {
                        $unchanged++;
                        continue;
                    }

                    $this->line("  {$photo->original_filename}  ->  {$name}");

                    if (!$dryRun) {
                        $photo->update(['original_filename' => $name]);
                    }

                    $changed++;
                }
            }
        }

        $verb = $dryRun ? 'would be renamed' : 'renamed';
        $this->info("{$changed} {$verb}, {$unchanged} already correct, {$unresolved} had no filename in their asset URL.");

        return self::SUCCESS;
    }
}
