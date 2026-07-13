<?php

namespace Tests\Feature\Admin;

use App\Models\Album;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlbumManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Total Photos');
    }

    public function test_authenticated_user_can_view_album_list(): void
    {
        $user = User::factory()->create();
        Album::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('admin.albums.index'));

        $response->assertOk();
    }

    public function test_album_can_be_created_with_default_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.albums.store'), [
            'name' => 'Summer Trip',
            'description' => 'Photos from summer',
        ]);

        $response->assertRedirect(route('admin.albums.create'));
        $this->assertDatabaseHas('albums', ['name' => 'Summer Trip']);

        $album = Album::where('name', 'Summer Trip')->firstOrFail();
        $this->assertNotNull($album->date_taken);
    }

    public function test_title_is_required_when_creating_album(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.albums.store'), [
            'description' => 'Missing title',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_album_can_be_updated(): void
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put(route('admin.albums.update', $album), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('admin.albums.index'));
        $this->assertDatabaseHas('albums', ['id' => $album->id, 'name' => 'New Name']);
    }

    public function test_album_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $album = Album::factory()->create();

        $response = $this->actingAs($user)->delete(route('admin.albums.destroy', $album));

        $response->assertRedirect(route('admin.albums.index'));
        $this->assertDatabaseMissing('albums', ['id' => $album->id]);
    }

    public function test_guest_cannot_access_albums(): void
    {
        $response = $this->get(route('admin.albums.index'));

        $response->assertRedirect(route('login'));
    }
}