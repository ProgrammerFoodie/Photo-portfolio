<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Download;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Sample albums/photos/downloads so the dashboard isn't empty on a
        // fresh install. Safe to comment out once you have real content.
        Album::factory(5)
            ->create()
            ->each(function (Album $album) {
                Photo::factory(rand(5, 30))->create(['album_id' => $album->id]);
                Download::factory(rand(0, 10))->create(['album_id' => $album->id]);
            });
    }
}