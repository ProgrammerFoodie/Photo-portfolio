<?php

namespace App\Jobs;

use App\Models\Photo;
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

            $this->photo->update([
                'thumbnail_path' => $thumbnailRelativePath,
                'width' => $width,
                'height' => $height,
                'status' => 'ready',
            ]);
        } catch (\Throwable $e) {
            $this->photo->update(['status' => 'failed']);
            throw $e;
        }
    }
}