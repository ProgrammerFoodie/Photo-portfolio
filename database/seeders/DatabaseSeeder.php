<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Download;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // The first user created (id 1) is the sole super-admin who can
        // manage other user accounts — see Gate::define('manage-users').
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'username' => 'admin', 'password' => Hash::make('password')],
        );

        // Sample albums/photos/downloads so the dashboard isn't empty on a
        // fresh install. Only runs once, on a genuinely empty install --
        // safe to re-run db:seed afterwards without piling up more demo data.
        if (Album::count() === 0) {
            Album::factory(5)
                ->create()
                ->each(function (Album $album) {
                    Photo::factory(rand(5, 30))->create(['album_id' => $album->id]);
                    Download::factory(rand(0, 10))->create(['album_id' => $album->id]);
                });
        }
    }
}