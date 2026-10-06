<?php

namespace Tests\Feature\ICloud;

use App\Jobs\SyncICloudAlbumJob;
use App\Models\Album;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ICloudAlbumAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Bus::fake([SyncICloudAlbumJob::class]);

        Http::fake([
            '*/sharedstreams/webstream' => Http::response([
                'streamName' => 'Iceland 2025',
                'streamCtag' => 'ctag-1',
                'photos' => [['photoGuid' => 'guid-1', 'derivatives' => []]],
            ]),
        ]);
    }

    public function test_guests_cannot_reach_the_link_form(): void
    {
        $this->get(route('admin.icloud.create'))->assertRedirect(route('login'));
    }

    public function test_the_link_form_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.icloud.create'))
            ->assertOk()
            ->assertSee('Shared album link');
    }

    public function test_linking_creates_an_album_and_queues_the_import(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.icloud.store'), [
            'link' => 'https://www.icloud.com/sharedalbum/#B0abcdefghij',
        ]);

        $response->assertRedirect(route('admin.albums.index'));

        $album = Album::firstOrFail();
        $this->assertSame('Iceland 2025', $album->name);
        $this->assertSame('B0abcdefghij', $album->icloud_token);
        $this->assertSame('syncing', $album->icloud_sync_status);
        $this->assertFalse($album->icloud_prune);

        // Never imported in the request: a large album would time out, and only
        // the queue worker (www-data) can write to the photos directory.
        Bus::assertDispatched(SyncICloudAlbumJob::class);
    }

    public function test_a_custom_title_overrides_the_icloud_name(): void
    {
        $this->actingAs($this->user)->post(route('admin.icloud.store'), [
            'link' => 'B0abcdefghij',
            'name' => 'My Iceland Trip',
        ]);

        $this->assertSame('My Iceland Trip', Album::firstOrFail()->name);
    }

    public function test_a_malformed_link_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('admin.icloud.store'), ['link' => 'nope'])
            ->assertSessionHasErrors('link');

        $this->assertSame(0, Album::count());
    }

    public function test_the_same_album_cannot_be_linked_twice(): void
    {
        Album::factory()->create(['icloud_token' => 'B0abcdefghij', 'name' => 'Already Here']);

        $this->actingAs($this->user)
            ->post(route('admin.icloud.store'), ['link' => 'B0abcdefghij'])
            ->assertSessionHasErrors('link');

        $this->assertSame(1, Album::count());
    }

    public function test_an_unreadable_link_creates_no_album(): void
    {
        Http::swap(new Factory());
        Http::fake(['*/sharedstreams/webstream' => Http::response('gone', 404)]);

        $this->actingAs($this->user)
            ->post(route('admin.icloud.store'), ['link' => 'B0abcdefghij'])
            ->assertSessionHasErrors('link');

        // Validated before creating, so a dead link leaves nothing behind.
        $this->assertSame(0, Album::count());
        Bus::assertNotDispatched(SyncICloudAlbumJob::class);
    }

    public function test_it_refuses_to_nest_under_a_subalbum(): void
    {
        $parent = Album::factory()->create();
        $child = Album::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->user)
            ->post(route('admin.icloud.store'), [
                'link' => 'B0abcdefghij',
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->assertSame(2, Album::count());
    }

    public function test_sync_now_queues_a_job(): void
    {
        $album = Album::factory()->create(['icloud_token' => 'B0abcdefghij']);

        $this->actingAs($this->user)
            ->post(route('admin.icloud.sync', $album))
            ->assertRedirect();

        Bus::assertDispatched(SyncICloudAlbumJob::class);
        $this->assertSame('syncing', $album->fresh()->icloud_sync_status);
    }

    public function test_sync_now_rejects_an_unlinked_album(): void
    {
        $album = Album::factory()->create(['icloud_token' => null]);

        $this->actingAs($this->user)
            ->post(route('admin.icloud.sync', $album))
            ->assertSessionHasErrors('icloud');

        Bus::assertNotDispatched(SyncICloudAlbumJob::class);
    }

    public function test_auto_sync_can_be_paused_and_resumed(): void
    {
        $album = Album::factory()->create(['icloud_token' => 'B0abcdefghij', 'icloud_auto_sync' => true]);

        $this->actingAs($this->user)
            ->patch(route('admin.icloud.update', $album), ['icloud_auto_sync' => 0]);

        $this->assertFalse($album->fresh()->icloud_auto_sync);

        $this->actingAs($this->user)
            ->patch(route('admin.icloud.update', $album), ['icloud_auto_sync' => 1]);

        $this->assertTrue($album->fresh()->icloud_auto_sync);
    }

    public function test_unlinking_keeps_the_photos(): void
    {
        $album = Album::factory()->create([
            'icloud_token' => 'B0abcdefghij',
            'icloud_stream_ctag' => 'ctag-1',
        ]);
        Photo::factory()->count(3)->create([
            'album_id' => $album->id,
            'icloud_photo_guid' => null,
        ]);

        $this->actingAs($this->user)
            ->delete(route('admin.icloud.destroy', $album))
            ->assertRedirect();

        $album->refresh();

        $this->assertNull($album->icloud_token);
        $this->assertNull($album->icloud_stream_ctag);
        $this->assertFalse($album->icloud_auto_sync);

        // Unlinking disconnects; it must never be a way to lose photos.
        $this->assertSame(3, $album->photos()->count());
    }

    public function test_the_albums_index_shows_icloud_state(): void
    {
        Album::factory()->create([
            'name' => 'Linked Album',
            'icloud_token' => 'B0abcdefghij',
            'icloud_sync_status' => 'failed',
            'icloud_sync_error' => 'Token revoked',
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.albums.index'))
            ->assertOk()
            ->assertSee('Failed')
            ->assertSee('Sync now');
    }
}
