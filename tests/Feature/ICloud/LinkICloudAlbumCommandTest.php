<?php

namespace Tests\Feature\ICloud;

use App\Jobs\GenerateThumbnailJob;
use App\Models\Album;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LinkICloudAlbumCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Bus::fake([GenerateThumbnailJob::class]);

        Http::fake([
            '*/sharedstreams/webstream' => Http::response([
                'streamName' => 'Iceland 2025',
                'streamCtag' => 'ctag-1',
                'photos' => [],
            ]),
        ]);
    }

    public function test_it_creates_an_album_named_after_the_shared_album(): void
    {
        $this->artisan('icloud:link', ['link' => 'https://www.icloud.com/sharedalbum/#B0abcdefghij'])
            ->expectsConfirmation('Create this album and import its photos?', 'yes')
            ->assertSuccessful();

        $album = Album::firstOrFail();

        $this->assertSame('Iceland 2025', $album->name);
        $this->assertSame('B0abcdefghij', $album->icloud_token);
        $this->assertStringStartsWith('iceland-2025-', $album->slug);

        // Destructive by nature, so it stays off until its reports are trusted.
        $this->assertFalse($album->icloud_prune);
        $this->assertTrue($album->icloud_auto_sync);
    }

    public function test_it_accepts_a_bare_token(): void
    {
        $this->artisan('icloud:link', ['link' => 'B0abcdefghij'])
            ->expectsConfirmation('Create this album and import its photos?', 'yes')
            ->assertSuccessful();

        $this->assertSame('B0abcdefghij', Album::firstOrFail()->icloud_token);
    }

    public function test_it_refuses_to_link_the_same_album_twice(): void
    {
        Album::factory()->create(['icloud_token' => 'B0abcdefghij']);

        $this->artisan('icloud:link', ['link' => 'B0abcdefghij'])->assertFailed();

        $this->assertSame(1, Album::count());
    }

    public function test_it_rejects_an_unreadable_link(): void
    {
        $this->artisan('icloud:link', ['link' => 'nope'])->assertFailed();

        $this->assertSame(0, Album::count());
    }

    public function test_it_creates_nothing_when_the_album_cannot_be_read(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*/sharedstreams/webstream' => Http::response('gone', 404)]);

        $this->artisan('icloud:link', ['link' => 'B0abcdefghij'])->assertFailed();

        // Checked before creating, so a dead link leaves no empty album behind.
        $this->assertSame(0, Album::count());
    }

    public function test_it_refuses_to_nest_under_a_subalbum(): void
    {
        $parent = Album::factory()->create();
        $child = Album::factory()->create(['parent_id' => $parent->id]);

        $this->artisan('icloud:link', ['link' => 'B0abcdefghij', '--parent' => $child->id])
            ->assertFailed();

        $this->assertSame(2, Album::count());
    }
}
