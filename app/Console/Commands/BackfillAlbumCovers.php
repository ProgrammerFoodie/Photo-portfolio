<?php

namespace App\Console\Commands;

use App\Models\Album;
use Illuminate\Console\Command;

class BackfillAlbumCovers extends Command
{
    protected $signature = 'albums:backfill-covers';

    protected $description = 'Set a cover on top-level albums that have none of their own but have a subalbum with photos.';

    public function handle(): int
    {
        $albums = Album::whereNull('parent_id')
            ->whereNull('cover_photo_id')
            ->with(['children' => fn ($query) => $query->orderBy('sort_order')->with([
                'photos' => fn ($query) => $query->orderBy('sort_order'),
            ])])
            ->get();

        $updated = 0;

        foreach ($albums as $album) {
            $coverPhoto = $album->children
                ->flatMap(fn ($child) => $child->photos)
                ->first();

            if ($coverPhoto === null) {
                continue;
            }

            $album->update(['cover_photo_id' => $coverPhoto->id]);
            $updated++;

            $this->line("Set cover for album \"{$album->name}\" (#{$album->id}) to photo #{$coverPhoto->id}.");
        }

        $this->info("Backfilled covers for {$updated} album(s).");

        return self::SUCCESS;
    }
}
