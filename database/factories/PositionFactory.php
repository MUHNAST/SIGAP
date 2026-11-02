<?php

namespace Database\Factories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         return [
            'nama' => fake()->jobTitle(),
            'tunjangan_jabatan' => fake()->numberBetween(
                1000000,
                5000000
            ),
            'deskripsi' => fake()->sentence(),
        ];
    }
}
