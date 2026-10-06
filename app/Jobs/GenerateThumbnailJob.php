<?php

namespace App\Jobs;

use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class GenerateThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public Photo $photo)
    {
    }

    public function handle(): void
    {
        $this->photo->update(['status' => 'processing']);

        try {
            $originalFullPath = Storage::disk('local')->path($this->photo->original_path);

            $manager = new ImageManager(new Driver());
            $image = $manager->decodePath($originalFullPath);

            $width = $image->width();
            $height = $image->height();

            $thumbnailRelativePath = 'photos/thumbnails/' . pathinfo($this->photo->original_path, PATHINFO_FILENAME) . '.jpg';
            Storage::disk('local')->makeDirectory('photos/thumbnails');
            $thumbnailFullPath = Storage::disk('local')->path($thumbnailRelativePath);

            $image->scaleDown(width: 600)->save($thumbnailFullPath, quality: 80);

            $update = [
                'thumbnail_path' => $thumbnailRelativePath,
                'width' => $width,
                'height' => $height,
                'status' => 'ready',
            ];

            // Only fall back to EXIF when nothing has already supplied a date.
            // iCloud strips most EXIF from the derivatives it serves, but the
            // shared-album API reports each photo's real capture time, so sync
            // sets captured_at up front. Overwriting it here would null it out
            // and break the album ordering, which sorts on
            // COALESCE(captured_at, created_at).
            if ($this->photo->captured_at === null) {
                $update['captured_at'] = $this->readCapturedAt($originalFullPath);
            }

            $this->photo->update($update);
        } catch (\Throwable $e) {
            $this->photo->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Read the photo's EXIF capture timestamp (when the shutter fired), so
     * albums can be sorted by when photos were actually taken rather than
     * upload order. Uploads are JPEG-only, so exif_read_data() applies to
     * every photo; returns null if the file has no EXIF date (e.g. a
     * screenshot or an image stripped of metadata).
     */
    private function readCapturedAt(string $path): ?Carbon
    {
        $exif = @exif_read_data($path);
        $raw = $exif['DateTimeOriginal'] ?? $exif['DateTime'] ?? null;

        if (!$raw) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y:m:d H:i:s', $raw);
        } catch (\Throwable) {
            return null;
        }
    }
}