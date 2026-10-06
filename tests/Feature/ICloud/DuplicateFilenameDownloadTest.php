<?php

namespace Tests\Feature\ICloud;

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Photos synced from iCloud keep their real camera filenames, so two photos in
 * one album can legitimately both be called IMG_3884.JPG.
 */
class DuplicateFilenameDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_zip_of_same_named_photos_contains_every_file(): void
    {
        Storage::fake('local');

        $album = Album::factory()->create();

        $photos = collect(range(1, 3))->map(function (int $i) use ($album) {
            $path = "photos/originals/{$i}.jpg";
            Storage::disk('local')->put($path, "bytes-{$i}");

            return Photo::factory()->create([
                'album_id' => $album->id,
                'original_filename' => 'IMG_3884.JPG',
                'original_path' => $path,
                'status' => 'ready',
            ]);
        });

        $response = $this->post(route('albums.downloadSelected', $album), [
            'photo_ids' => $photos->pluck('id')->all(),
        ]);

        $response->assertOk();

        $zipPath = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($zipPath, $response->streamedContent());

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true);

        // Without de-duplication the archive would hold one usable entry and
        // the visitor would silently receive fewer photos than they selected.
        $this->assertSame(3, $zip->numFiles);

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }

        $this->assertSame($names, array_unique($names));
        $this->assertContains('IMG_3884.JPG', $names);
        $this->assertContains('IMG_3884 (2).JPG', $names);
        $this->assertContains('IMG_3884 (3).JPG', $names);

        $zip->close();
        @unlink($zipPath);
    }
}
