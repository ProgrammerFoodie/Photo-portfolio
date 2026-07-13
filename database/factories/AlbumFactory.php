<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    public function definition(): array
    {
        $name = ucwords($this->faker->words(3, true));

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . $this->faker->unique()->numerify('####'),
            'description' => $this->faker->sentence(),
            'date_taken' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'location' => $this->faker->city(),
        ];
    }
}