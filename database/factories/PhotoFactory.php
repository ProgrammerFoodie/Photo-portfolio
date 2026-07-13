<?php

namespace Database\Factories;

use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    public function definition(): array
    {
        $uuid = $this->faker->uuid();

        return [
            'album_id' => Album::factory(),
            'original_filename' => $uuid . '.jpg',
            'original_path' => 'photos/originals/' . $uuid . '.jpg',
            'thumbnail_path' => 'photos/thumbnails/' . $uuid . '.jpg',
            'filesize' => $this->faker->numberBetween(300_000, 9_000_000),
            'width' => 1920,
            'height' => 1080,
            'status' => 'ready',
        ];
    }
}