<?php

namespace Tests\Feature\ICloud;

use App\Jobs\GenerateThumbnailJob;
use App\Models\Album;
use App\Models\Photo;
use App\Services\ICloud\SharedAlbumSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedAlbumSyncTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'B0abcdefghij';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Bus::fake([GenerateThumbnailJob::class]);
    }

    /** A real JPEG, because the sync verifies downloaded bytes really are one. */
    private function jpegBytes(): string
    {
        $image = imagecreatetruecolor(4, 4);
        ob_start();
        imagejpeg($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    private function photoPayload(string $guid, string $checksum, string $date = '2025-06-14T10:30:45Z'): array
    {
        return [
            'photoGuid' => $guid,
            'caption' => '',
            'dateCreated' => $date,
            'batchDateCreated' => $date,
            'derivatives' => [
                '342' => ['checksum' => $checksum . '-small', 'fileSize' => 1000, 'width' => 256, 'height' => 342],
                '2048' => ['checksum' => $checksum, 'fileSize' => 500000, 'width' => 1536, 'height' => 2048],
            ],
        ];
    }

    /**
     * @param  list<array>  $photos
     */
    private function fakeICloud(array $photos, string $ctag = 'ctag-1', array $extra = []): void
    {
        $assetItems = [];
        foreach ($photos as $photo) {
            foreach ($photo['derivatives'] as $derivative) {
                $assetItems[$derivative['checksum']] = [
                    'url_location' => 'cvws.icloud-content.com',
                    // Apple's real URLs carry the original camera filename here.
                    'url_path' => '/S/token/' . $derivative['checksum'] . '.JPG',
                ];
            }
        }

        // Http::fake() *merges* stubs and the first match wins, so a second
        // call in the same test would otherwise keep answering with the first
        // call's responses. Swap in a clean factory to get replace semantics.
        Http::swap(new Factory());

        // Union, not array_merge: on an identical key array_merge would let the
        // wildcard below overwrite a deliberate override. "+" keeps the
        // left-hand value and its ordering, so $extra always wins.
        Http::fake($extra + [
            '*/sharedstreams/webstream' => Http::response([
                'streamName' => 'Iceland 2025',
                'streamCtag' => $ctag,
                'itemsReturned' => count($photos),
                'photos' => $photos,
            ]),
            '*/sharedstreams/webasseturls' => Http::response(['items' => $assetItems]),
            'cvws.icloud-content.com/*' => Http::response($this->jpegBytes()),
        ]);
    }

    private function linkedAlbum(array $attributes = []): Album
    {
        return Album::factory()->create(array_merge([
            'icloud_token' => self::TOKEN,
            'icloud_auto_sync' => true,
            'icloud_prune' => false,
        ], $attributes));
    }

    private function sync(): SharedAlbumSyncService
    {
        return app(SharedAlbumSyncService::class);
    }

    public function test_first_sync_imports_photos_with_guid_and_capture_date(): void
    {
        $this->fakeICloud([
            $this->photoPayload('guid-1', 'sum-1'),
            $this->photoPayload('guid-2', 'sum-2', '2025-06-15T08:00:00Z'),
        ]);

        $album = $this->linkedAlbum();

        $result = $this->sync()->sync($album);

        $this->assertSame(2, $result->created);
        $this->assertSame(0, $result->failed);
        $this->assertSame(2, $album->photos()->count());

        $photo = Photo::where('icloud_photo_guid', 'guid-1')->firstOrFail();
        $this->assertSame('pending', $photo->status);
        $this->assertSame('sum-1', $photo->icloud_checksum);
        $this->assertSame(2048, $photo->height);

        // Set from the stream, not EXIF — iCloud strips EXIF from derivatives.
        $this->assertNotNull($photo->captured_at);
        $this->assertSame('2025-06-14 10:30:45', $photo->captured_at->format('Y-m-d H:i:s'));

        Storage::disk('local')->assertExists($photo->original_path);
        Bus::assertDispatched(GenerateThumbnailJob::class, 2);
    }

    public function test_the_original_camera_filename_is_taken_from_the_asset_url(): void
    {
        // Apple serves ".../IMG_3850.JPG?o=..." — that name is what the same
        // photo is called in the master library, so it's the handle that makes
        // a full-resolution request fulfillable.
        $this->fakeICloud([$this->photoPayload('guid-1', 'IMG_3850')]);

        $this->sync()->sync($this->linkedAlbum());

        $this->assertSame('IMG_3850.JPG', Photo::firstOrFail()->original_filename);
    }

    public function test_filename_falls_back_to_the_capture_timestamp(): void
    {
        $this->fakeICloud(
            [$this->photoPayload('guid-1', 'sum-1')],
            'ctag-1',
            // An opaque path with no recognisable filename.
            ['cvws.icloud-content.com/*' => Http::response($this->jpegBytes())],
        );

        Http::swap(new Factory());
        Http::fake([
            '*/sharedstreams/webstream' => Http::response([
                'streamName' => 'Iceland 2025',
                'streamCtag' => 'ctag-1',
                'photos' => [$this->photoPayload('guid-1', 'sum-1')],
            ]),
            '*/sharedstreams/webasseturls' => Http::response(['items' => [
                'sum-1' => ['url_location' => 'cvws.icloud-content.com', 'url_path' => '/S/opaque-token'],
                'sum-1-small' => ['url_location' => 'cvws.icloud-content.com', 'url_path' => '/S/opaque-token'],
            ]]),
            'cvws.icloud-content.com/*' => Http::response($this->jpegBytes()),
        ]);

        $this->sync()->sync($this->linkedAlbum());

        $this->assertSame('2025-06-14-103045-guid-1.jpg', Photo::firstOrFail()->original_filename);
    }

    public function test_the_largest_derivative_is_chosen(): void
    {
        $this->fakeICloud([$this->photoPayload('guid-1', 'sum-1')]);

        $this->sync()->sync($this->linkedAlbum());

        $this->assertSame('sum-1', Photo::firstOrFail()->icloud_checksum);
    }

    public function test_unchanged_ctag_creates_nothing_and_does_not_fetch_assets(): void
    {
        $this->fakeICloud([$this->photoPayload('guid-1', 'sum-1')], 'ctag-1');

        $album = $this->linkedAlbum(['icloud_stream_ctag' => 'ctag-1']);

        $result = $this->sync()->sync($album);

        $this->assertTrue($result->unchanged);
        $this->assertSame(0, $album->photos()->count());

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'webasseturls'));
    }

    public function test_resync_only_imports_new_photos(): void
    {
        $this->fakeICloud([$this->photoPayload('guid-1', 'sum-1')]);
        $album = $this->linkedAlbum();
        $this->sync()->sync($album);

        $this->fakeICloud([
            $this->photoPayload('guid-1', 'sum-1'),
            $this->photoPayload('guid-2', 'sum-2'),
        ], 'ctag-2');

        $result = $this->sync()->sync($album->fresh());

        $this->assertSame(1, $result->created);
        $this->assertSame(2, $album->photos()->count());
    }

    public function test_removed_photo_is_reported_but_not_deleted_when_pruning_is_off(): void
    {
        $this->fakeICloud([
            $this->photoPayload('guid-1', 'sum-1'),
            $this->photoPayload('guid-2', 'sum-2'),
        ]);
        $album = $this->linkedAlbum();
        $this->sync()->sync($album);

        $this->fakeICloud([$this->photoPayload('guid-1', 'sum-1')], 'ctag-2');

        $result = $this->sync()->sync($album->fresh());

        $this->assertSame(1, $result->prunable);
        $this->assertSame(0, $result->pruned);
        $this->assertSame(2, $album->photos()->count());
    }

    public function test_removed_photo_is_deleted_with_its_files_when_pruning_is_on(): void
    {
        $this->fakeICloud([
            $this->photoPayload('guid-1', 'sum-1'),
            $this->photoPayload('guid-2', 'sum-2'),
        ]);
        $album = $this->linkedAlbum(['icloud_prune' => true]);
        $this->sync()->sync($album);

        $gone = Photo::where('icloud_photo_guid', 'guid-2')->firstOrFail();
        $path = $gone->original_path;

        $this->fakeICloud([$this->photoPayload('guid-1', 'sum-1')], 'ctag-2');

        $result = $this->sync()->sync($album->fresh());

        $this->assertSame(1, $result->pruned);
        $this->assertSame(1, $album->photos()->count());
        $this->assertDatabaseMissing('photos', ['id' => $gone->id]);

        // Deleted through Eloquent so Photo::deleting cleans up the disk.
        Storage::disk('local')->assertMissing($path);
    }

    public function test_an_empty_remote_never_prunes(): void
    {
        $this->fakeICloud([
            $this->photoPayload('guid-1', 'sum-1'),
            $this->photoPayload('guid-2', 'sum-2'),
        ]);
        $album = $this->linkedAlbum(['icloud_prune' => true]);
        $this->sync()->sync($album);

        // An empty list is far more likely a broken response than a genuinely
        // emptied album, and acting on it would wipe the gallery.
        $this->fakeICloud([], 'ctag-empty');

        $result = $this->sync()->sync($album->fresh());

        $this->assertSame(0, $result->pruned);
        $this->assertSame(2, $result->prunable);
        $this->assertSame(2, $album->photos()->count());
    }

    public function test_revoked_token_marks_failed_without_touching_existing_photos(): void
    {
        $this->fakeICloud([$this->photoPayload('guid-1', 'sum-1')]);
        $album = $this->linkedAlbum();
        $this->sync()->sync($album);

        Http::swap(new Factory());
        Http::fake(['*/sharedstreams/webstream' => Http::response('gone', 404)]);

        $result = $this->sync()->sync($album->fresh());

        $this->assertNotNull($result->error);
        $this->assertSame('failed', $album->fresh()->icloud_sync_status);
        $this->assertSame(1, $album->photos()->count());
    }

    public function test_one_failed_asset_does_not_abort_the_batch(): void
    {
        $this->fakeICloud(
            [
                $this->photoPayload('guid-1', 'sum-1'),
                $this->photoPayload('guid-2', 'sum-2'),
            ],
            'ctag-1',
            ['cvws.icloud-content.com/S/token/sum-1.JPG' => Http::response('nope', 500)],
        );

        $result = $this->sync()->sync($this->linkedAlbum());

        $this->assertSame(1, $result->created);
        $this->assertSame(1, $result->failed);
        $this->assertDatabaseHas('photos', ['icloud_photo_guid' => 'guid-2']);
    }

    public function test_non_jpeg_download_is_rejected_and_leaves_no_file(): void
    {
        $this->fakeICloud(
            [$this->photoPayload('guid-1', 'sum-1')],
            'ctag-1',
            ['cvws.icloud-content.com/*' => Http::response('<html>not an image</html>')],
        );

        $result = $this->sync()->sync($this->linkedAlbum());

        $this->assertSame(0, $result->created);
        $this->assertSame(1, $result->failed);
        $this->assertSame(0, Photo::count());
        $this->assertEmpty(Storage::disk('local')->files('photos/originals'));
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->fakeICloud([$this->photoPayload('guid-1', 'sum-1')]);
        $album = $this->linkedAlbum();

        $result = $this->sync()->sync($album, dryRun: true);

        $this->assertSame(1, $result->created);
        $this->assertSame(0, Photo::count());
        $this->assertNull($album->fresh()->icloud_stream_ctag);
    }

    public function test_first_photo_becomes_the_album_cover(): void
    {
        $this->fakeICloud([$this->photoPayload('guid-1', 'sum-1')]);
        $album = $this->linkedAlbum();

        $this->sync()->sync($album);

        $this->assertSame(Photo::firstOrFail()->id, $album->fresh()->cover_photo_id);
    }

    public function test_album_without_a_token_is_skipped(): void
    {
        $album = Album::factory()->create(['icloud_token' => null]);

        $this->assertTrue($this->sync()->sync($album)->skipped);
    }

    public function test_captured_at_survives_thumbnail_generation(): void
    {
        // GenerateThumbnailJob must not overwrite a date the sync already set,
        // or album ordering (COALESCE(captured_at, created_at)) breaks.
        $photo = Photo::factory()->create([
            'captured_at' => '2025-06-14 10:30:45',
            'status' => 'pending',
        ]);

        Storage::disk('local')->put($photo->original_path, $this->jpegBytes());

        (new GenerateThumbnailJob($photo))->handle();

        $this->assertSame('2025-06-14 10:30:45', $photo->fresh()->captured_at->format('Y-m-d H:i:s'));
        $this->assertSame('ready', $photo->fresh()->status);
    }
}
