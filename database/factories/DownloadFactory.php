<?php

namespace Database\Factories;

use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;

class DownloadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'album_id' => Album::factory(),
            'photo_id' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}