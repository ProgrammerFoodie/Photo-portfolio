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
     * Store an incoming chunk in its own indexed file inside a per-upload
     * directory.
     *
     * One file per index makes a client retry idempotent: re-sending chunk 3
     * overwrites the same file rather than appending its bytes a second time.
     * The previous append-mode approach silently corrupted the assembled photo
     * whenever the browser retried a chunk whose response was lost.
     */
    public function storeChunk(UploadedFile $chunk, string $uploadId, int $chunkIndex): void
    {
        $disk = Storage::disk('local');
        $dir = self::CHUNK_DIR . '/' . $uploadId;
        $disk->makeDirectory($dir);

        $chunk->storeAs($dir, $chunkIndex . '.part', 'local');
    }

    /**
     * Called once the client confirms all chunks for a file have been sent.
     * Verifies every expected chunk arrived, concatenates them in index order,
     * confirms the result is genuinely a JPEG, then moves it into permanent
     * storage and creates the Photo record.
     */
    public function finalizeUpload(
        string $uploadId,
        string $originalFilename,
        Album $album,
        int $expectedTotalChunks
    ): Photo {
        $disk = Storage::disk('local');
        $dir = self::CHUNK_DIR . '/' . $uploadId;

        // Refuse to assemble a truncated upload. Previously the chunk count was
        // accepted but never checked, so a dropped chunk produced a silently
        // broken photo instead of an error.
        for ($i = 0; $i < $expectedTotalChunks; $i++) {
            if (!$disk->exists($dir . '/' . $i . '.part')) {
                throw new \RuntimeException("Missing chunk {$i} for upload {$uploadId}.");
            }
        }

        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $storedFilename = Str::uuid() . '.' . $extension;
        $relativePath = self::PHOTOS_DIR . '/' . $storedFilename;

        $disk->makeDirectory(self::PHOTOS_DIR);

        $finalPath = $disk->path($relativePath);

        // Concatenate in numeric index order — arrival order is not reliable.
        $out = fopen($finalPath, 'wb');
        for ($i = 0; $i < $expectedTotalChunks; $i++) {
            $in = fopen($disk->path($dir . '/' . $i . '.part'), 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        $disk->deleteDirectory($dir);

        // The filename extension is attacker-controlled; verify the actual
        // bytes are a real JPEG before this file is ever served to a visitor.
        $info = @getimagesize($finalPath);
        if ($info === false || $info[2] !== IMAGETYPE_JPEG) {
            @unlink($finalPath);
            throw new \RuntimeException('Uploaded file is not a valid JPEG image.');
        }

        $photo = Photo::create([
            'album_id' => $album->id,
            'original_filename' => $originalFilename,
            'original_path' => $relativePath,
            'filesize' => filesize($finalPath),
            'status' => 'pending',
        ]);

        // First photo ever uploaded to an album becomes its cover by default,
        // until someone picks a different one in the admin panel.
        $album->assignCoverIfMissing($photo);

        GenerateThumbnailJob::dispatch($photo);

        return $photo;
    }
}