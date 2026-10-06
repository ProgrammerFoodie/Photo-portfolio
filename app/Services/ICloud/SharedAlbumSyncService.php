<?php

namespace App\Services\ICloud;

use App\Jobs\GenerateThumbnailJob;
use App\Models\Album;
use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pulls an iCloud shared album into a local Album.
 *
 * One-way: iCloud is the source of truth for *photos*. Curation — an album's
 * description, date, location, cover and ordering — belongs to the site and is
 * never overwritten by a sync.
 */
class SharedAlbumSyncService
{
    private const PHOTOS_DIR = 'photos/originals';

    /**
     * Refuse to start a sync with less than this much room left. The VPS runs a
     * single 25GB filesystem shared with MySQL, logs and the OS, so filling it
     * takes the whole site down rather than merely failing the import.
     */
    private const MIN_FREE_BYTES = 1073741824; // 1 GB

    public function __construct(private readonly SharedAlbumClient $client)
    {
    }

    public function sync(Album $album, bool $dryRun = false): SyncResult
    {
        if (!$album->icloud_token) {
            return SyncResult::skipped('no iCloud token linked');
        }

        if (($free = $this->freeBytes()) !== null && $free < self::MIN_FREE_BYTES) {
            $result = SyncResult::skipped(sprintf(
                'only %s free on disk, need %s',
                $this->humanBytes($free),
                $this->humanBytes(self::MIN_FREE_BYTES),
            ));

            $this->fail($album, $result->error, $dryRun);

            return $result;
        }

        if (!$dryRun) {
            $album->update(['icloud_sync_status' => 'syncing']);
        }

        try {
            return $this->run($album, $dryRun);
        } catch (\Throwable $e) {
            // A revoked or unshared link lands here. Existing photos are left
            // alone on purpose: losing access upstream must never empty a
            // gallery that is currently serving visitors.
            $this->fail($album, $e->getMessage(), $dryRun);

            Log::channel('icloud')->error("Sync failed for album {$album->id} ({$album->name}): {$e->getMessage()}");

            return new SyncResult(error: $e->getMessage());
        }
    }

    private function run(Album $album, bool $dryRun): SyncResult
    {
        $fetched = $this->client->fetchStream($album->icloud_token, $album->icloud_stream_ctag);

        if ($fetched === null) {
            if (!$dryRun) {
                $album->update([
                    'icloud_last_synced_at' => now(),
                    'icloud_sync_status' => 'idle',
                    'icloud_sync_error' => null,
                ]);
            }

            return SyncResult::unchanged();
        }

        $stream = $fetched['stream'];
        $base = $fetched['base'];

        /** @var array<string, array> $remote */
        $remote = [];
        foreach ($stream['photos'] ?? [] as $photo) {
            if (!empty($photo['photoGuid'])) {
                $remote[$photo['photoGuid']] = $photo;
            }
        }

        $local = $album->photos()
            ->whereNotNull('icloud_photo_guid')
            ->pluck('id', 'icloud_photo_guid')
            ->all();

        $toCreate = array_diff_key($remote, $local);
        $toPrune = array_diff_key($local, $remote);

        $created = 0;
        $failed = 0;

        foreach (array_chunk($toCreate, SharedAlbumClient::ASSET_BATCH_SIZE, true) as $batch) {
            if ($dryRun) {
                $created += count($batch);
                continue;
            }

            $created += $this->importBatch($album, $base, $batch, $failed);
        }

        [$pruned, $prunable] = $this->handlePrune($album, $toPrune, count($remote), $dryRun);

        if (!$dryRun) {
            $album->update([
                'icloud_stream_ctag' => $stream['streamCtag'] ?? null,
                'icloud_last_synced_at' => now(),
                'icloud_sync_status' => 'idle',
                'icloud_sync_error' => null,
            ]);
        }

        $result = new SyncResult(
            created: $created,
            failed: $failed,
            pruned: $pruned,
            prunable: $prunable,
        );

        Log::channel('icloud')->info("Album {$album->id} ({$album->name}): {$result->summary()}");

        return $result;
    }

    /**
     * Resolve and download one batch. Asset URLs are signed and short-lived, so
     * they are fetched here rather than all upfront — otherwise the tail of a
     * large album would be downloading against URLs that had already expired.
     *
     * @param  array<string, array>  $batch
     */
    private function importBatch(Album $album, string $base, array $batch, int &$failed): int
    {
        $derivatives = [];
        foreach ($batch as $guid => $photo) {
            if ($derivative = $this->client->largestDerivative($photo)) {
                $derivatives[$guid] = $derivative;
            } else {
                $failed++;
                Log::channel('icloud')->warning("Photo {$guid} has no usable derivative; skipped.");
            }
        }

        if ($derivatives === []) {
            return 0;
        }

        $urls = $this->client->fetchAssetUrls($base, array_keys($derivatives));
        $created = 0;

        foreach ($derivatives as $guid => $derivative) {
            $url = $urls[$derivative['checksum']] ?? null;

            if ($url === null) {
                $failed++;
                Log::channel('icloud')->warning("No asset URL returned for photo {$guid}; skipped.");
                continue;
            }

            try {
                // One photo failing must not abandon the rest of the album.
                $this->importPhoto($album, $guid, $derivative, $url, $batch[$guid]);
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                Log::channel('icloud')->warning("Failed to import photo {$guid}: {$e->getMessage()}");
            }
        }

        return $created;
    }

    private function importPhoto(Album $album, string $guid, array $derivative, string $url, array $remotePhoto): void
    {
        $response = Http::timeout(120)->get($url);
        $response->throw();

        $disk = Storage::disk('local');
        $disk->makeDirectory(self::PHOTOS_DIR);

        $relativePath = self::PHOTOS_DIR . '/' . Str::uuid() . '.jpg';
        $disk->put($relativePath, $response->body());

        $fullPath = $disk->path($relativePath);

        // The upload path verifies bytes are genuinely JPEG before anything is
        // ever served to a visitor; hold downloads to the same standard rather
        // than trusting the content-type header.
        $info = @getimagesize($fullPath);

        if ($info === false || $info[2] !== IMAGETYPE_JPEG) {
            $disk->delete($relativePath);
            throw new \RuntimeException('Downloaded asset is not a valid JPEG.');
        }

        $photo = Photo::create([
            'album_id' => $album->id,
            'original_filename' => $this->filenameFor($remotePhoto, $guid, $url),
            'original_path' => $relativePath,
            'filesize' => $disk->size($relativePath),
            'width' => $derivative['width'],
            'height' => $derivative['height'],
            // Set here rather than left to GenerateThumbnailJob: iCloud strips
            // EXIF from derivatives, so the stream's own timestamp is the only
            // record of when the shutter actually fired.
            'captured_at' => $this->capturedAt($remotePhoto),
            'status' => 'pending',
            'icloud_photo_guid' => $guid,
            'icloud_checksum' => $derivative['checksum'],
        ]);

        $album->assignCoverIfMissing($photo);

        GenerateThumbnailJob::dispatch($photo);
    }

    /**
     * @param  array<string, int>  $toPrune  guid => photo id
     * @return array{0: int, 1: int}  [pruned, prunable]
     */
    private function handlePrune(Album $album, array $toPrune, int $remoteCount, bool $dryRun): array
    {
        if ($toPrune === []) {
            return [0, 0];
        }

        // An empty remote list is far more likely to be a failed or truncated
        // response than a genuinely emptied album, and acting on it would
        // delete every synced photo at once.
        if ($remoteCount === 0) {
            Log::channel('icloud')->warning(
                "Album {$album->id}: remote returned zero photos; refusing to prune " . count($toPrune) . ' local photos.'
            );

            return [0, count($toPrune)];
        }

        if (!$album->icloud_prune || $dryRun) {
            return [0, count($toPrune)];
        }

        // Through Eloquent, never a mass delete: Photo::deleting is what
        // removes the original and thumbnail files from disk.
        $pruned = 0;
        foreach (Photo::whereIn('id', array_values($toPrune))->get() as $photo) {
            $photo->delete();
            $pruned++;
        }

        return [$pruned, 0];
    }

    /**
     * Work out the most useful name for a photo.
     *
     * Apple's signed asset URLs carry the original camera filename in their
     * path (".../IMG_3850.JPG?o=..."), which is the best possible handle: it's
     * exactly what the same photo is called in the master library, so a request
     * for a full-resolution original can be matched without guesswork.
     *
     * `caption` is checked first but is almost always empty. Failing both, fall
     * back to a name built from the capture timestamp, which at least locates
     * the photo by moment.
     */
    private function filenameFor(array $remotePhoto, string $guid, ?string $url = null): string
    {
        $caption = trim((string) ($remotePhoto['caption'] ?? ''));

        if ($caption !== '' && preg_match('~\.(jpe?g)$~i', $caption) === 1) {
            return $caption;
        }

        if ($url !== null && ($fromUrl = $this->filenameFromUrl($url)) !== null) {
            return $fromUrl;
        }

        $stamp = $this->capturedAt($remotePhoto)?->format('Y-m-d-His') ?? 'undated';

        return $stamp . '-' . substr($guid, 0, 8) . '.jpg';
    }

    public function filenameFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path)) {
            return null;
        }

        // Decode before inspecting: a name like "_DSC7310.JPG" can arrive
        // percent-encoded, and comparing the raw form would reject a perfectly
        // good filename and fall back to a generated one.
        $name = rawurldecode(basename($path));

        // basename() has already removed any directory part; this rejects the
        // leftovers that could still cause trouble in a Content-Disposition
        // header or a zip entry.
        if ($name === '' || strlen($name) > 200 || preg_match('~[/\\\\\x00-\x1f]~', $name) === 1) {
            return null;
        }

        // Must end in a JPEG extension and contain at least one ordinary
        // character — anything else is an opaque path token, not a filename.
        if (preg_match('~^[\p{L}\p{N} ._\-()+&\'#]+\.(jpe?g)$~iu', $name) !== 1) {
            return null;
        }

        return $name;
    }

    private function capturedAt(array $remotePhoto): ?Carbon
    {
        $raw = $remotePhoto['dateCreated'] ?? $remotePhoto['batchDateCreated'] ?? null;

        if (!$raw) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function fail(Album $album, ?string $message, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $album->update([
            'icloud_sync_status' => 'failed',
            'icloud_sync_error' => $message,
            'icloud_last_synced_at' => now(),
        ]);
    }

    private function freeBytes(): ?int
    {
        $bytes = @disk_free_space(Storage::disk('local')->path(''));

        return is_float($bytes) || is_int($bytes) ? (int) $bytes : null;
    }

    private function humanBytes(int $bytes): string
    {
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
