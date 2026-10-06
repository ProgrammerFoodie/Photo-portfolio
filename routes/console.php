<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Uploads that are started but never finalized (browser closed, network drop)
// leave their chunk data behind forever. Purge anything older than a day.
Schedule::call(function () {
    $disk = Storage::disk('local');
    $cutoff = now()->subDay()->getTimestamp();

    // Current format: one directory per upload id, holding N indexed parts.
    foreach ($disk->directories('chunks') as $dir) {
        if ($disk->lastModified($dir) < $cutoff) {
            $disk->deleteDirectory($dir);
        }
    }

    // Legacy format: a single "<uploadId>.part" file per upload, left over
    // from before chunks were stored per-index. Harmless but never reclaimed.
    foreach ($disk->files('chunks') as $file) {
        if ($disk->lastModified($file) < $cutoff) {
            $disk->delete($file);
        }
    }
})->daily()->name('purge-stale-upload-chunks')->withoutOverlapping();

// Pull new photos from every linked iCloud shared album. Albums whose ctag is
// unchanged cost a single request, so running this hourly is cheap.
//
// The command only enqueues; the queue worker does the downloading. That split
// matters: this crontab belongs to admin, but the photos directory is owned by
// www-data (0700), so importing in-process here would fail on every write.
// Every five minutes rather than hourly: an album being actively uploaded to
// iCloud trickles in over hours, and an hour of lag makes the site look broken.
// An album whose ctag is unchanged costs exactly one request, so the extra runs
// are close to free.
Schedule::command('icloud:sync')
    ->everyFiveMinutes()
    ->name('icloud-album-sync')
    ->withoutOverlapping();
