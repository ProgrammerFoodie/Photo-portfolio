<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Photo;
use App\Jobs\GenerateThumbnailJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoUploadService
{
    private const CHUNK_DIR = 'chunks';
    private const PHOTOS_DIR = 'photos/originals';

    /**
     * Store an incoming chunk on disk, appended to its assembly file.
     */
    public function storeChunk(UploadedFile $chunk, string $uploadId, int $chunkIndex): void
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory(self::CHUNK_DIR);

        $chunkPath = $disk->path(self::CHUNK_DIR . '/' . $uploadId . '.part');

        // Append mode: stream the chunk directly onto disk without loading
        // the whole assembled file into PHP memory.
        $source = fopen($chunk->getRealPath(), 'rb');
        $destination = fopen($chunkPath, 'ab');

        stream_copy_to_stream($source, $destination);

        fclose($source);
        fclose($destination);
    }

    /**
     * Called once the client confirms all chunks for a file have been sent.
     * Moves the assembled file into permanent storage and creates the Photo record.
     */
    public function finalizeUpload(
        string $uploadId,
        string $originalFilename,
        Album $album,
        int $expectedTotalChunks
    ): Photo {
        $disk = Storage::disk('local');
        $chunkPath = $disk->path(self::CHUNK_DIR . '/' . $uploadId . '.part');

        if (!file_exists($chunkPath)) {
            throw new \RuntimeException('No chunks found for this upload session.');
        }

        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $storedFilename = Str::uuid() . '.' . $extension;
        $relativePath = self::PHOTOS_DIR . '/' . $storedFilename;

        $disk->makeDirectory(self::PHOTOS_DIR);

        $finalPath = $disk->path($relativePath);
        rename($chunkPath, $finalPath);

        $photo = Photo::create([
            'album_id' => $album->id,
            'original_filename' => $originalFilename,
            'original_path' => $relativePath,
            'filesize' => filesize($finalPath),
            'status' => 'pending',
        ]);

        // First photo ever uploaded to an album becomes its cover by default,
        // until someone picks a different one in the admin panel.
        Album::where('id', $album->id)
            ->whereNull('cover_photo_id')
            ->update(['cover_photo_id' => $photo->id]);

        // If this album is a subalbum, also use this photo as the parent
        // album's cover when the parent has none of its own yet — otherwise
        // parent albums whose only photos live in a subalbum stay coverless.
        if ($album->parent_id !== null) {
            Album::where('id', $album->parent_id)
                ->whereNull('cover_photo_id')
                ->update(['cover_photo_id' => $photo->id]);
        }

        GenerateThumbnailJob::dispatch($photo);

        return $photo;
    }
}