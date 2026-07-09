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

        GenerateThumbnailJob::dispatch($photo);

        return $photo;
    }
}