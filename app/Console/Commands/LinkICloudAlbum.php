<?php

namespace App\Console\Commands;

use App\Jobs\SyncICloudAlbumJob;
use App\Models\Album;
use App\Services\ICloud\SharedAlbumClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Registers an iCloud shared album and creates the local Album to hold it.
 *
 * The album is created *from* the shared album rather than adopted into one the
 * user made first, because iCloud is the source of truth for what exists.
 */
class LinkICloudAlbum extends Command
{
    protected $signature = 'icloud:link
        {link : Share link (https://www.icloud.com/sharedalbum/#B0...) or bare token}
        {--parent= : ID of an existing top-level album to nest this under}
        {--name= : Override the album name (defaults to the iCloud album name)}
        {--no-sync : Create the link without importing photos yet}';

    protected $description = 'Link an iCloud shared album and import its photos';

    public function handle(SharedAlbumClient $client): int
    {
        $token = SharedAlbumClient::parseToken($this->argument('link'));

        if ($token === null) {
            $this->error('Could not read an album token from that link.');

            return self::FAILURE;
        }

        if ($existing = Album::where('icloud_token', $token)->first()) {
            $this->error("That shared album is already linked to album #{$existing->id} ({$existing->name}).");

            return self::FAILURE;
        }

        // Fetch before creating anything, so a bad or revoked link fails here
        // rather than leaving an empty album behind.
        $this->info('Checking the shared album...');

        try {
            $fetched = $client->fetchStream($token);
        } catch (\Throwable $e) {
            $this->error("Could not read that shared album: {$e->getMessage()}");

            return self::FAILURE;
        }

        $stream = $fetched['stream'];
        $name = $this->option('name') ?: ($stream['streamName'] ?? 'Untitled iCloud album');
        $count = count($stream['photos'] ?? []);

        $parent = null;
        if ($parentId = $this->option('parent')) {
            $parent = Album::find($parentId);

            if (!$parent) {
                $this->error("No album with ID {$parentId}.");

                return self::FAILURE;
            }

            if (!$parent->canHaveSubAlbums()) {
                $this->error("Album #{$parent->id} is itself a subalbum; albums nest only two levels deep.");

                return self::FAILURE;
            }
        }

        $this->line("  Name:   {$name}");
        $this->line("  Photos: {$count}");

        if ($parent) {
            $this->line("  Parent: #{$parent->id} {$parent->name}");
        }

        if (!$this->option('no-interaction') && !$this->confirm('Create this album and import its photos?', true)) {
            return self::SUCCESS;
        }

        $album = Album::create([
            'parent_id' => $parent?->id,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'date_taken' => now()->toDateString(),
            'icloud_token' => $token,
            'icloud_auto_sync' => true,
            'icloud_prune' => false,
        ]);

        $this->info("Created album #{$album->id} ({$album->slug}).");

        if ($this->option('no-sync')) {
            $this->comment('Skipped import. Run: php artisan icloud:sync ' . $album->id);

            return self::SUCCESS;
        }

        // Queued rather than run here: the photos directory is owned by
        // www-data (0700) and this command usually runs as admin, so importing
        // in-process would fail on the first file write. The worker runs as
        // www-data and can write.
        SyncICloudAlbumJob::dispatch($album);

        $this->info("Import queued — {$count} photo(s). Watch it with:");
        $this->line('  tail -f storage/logs/icloud-sync.log');

        return self::SUCCESS;
    }
}
