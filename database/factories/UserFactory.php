<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // public function definition(): array
    // {
    //     return [
    //         'email' => fake()->unique()->safeEmail(),
    //         'username' => fake()->unique()->userName(),
    //         'email_verified_at' => now(),
    //         'password' => static::$password ??= Hash::make('123456789'),
    //          'role' => fake()->randomElement([
    //             'super_admin',
    //             'admin',
    //             'user'
    //         ]),
    //         'remember_token' => Str::random(10),
    //         'no_hp' => fake()->unique()->numerify('08##########'),
    //     ];
    // }
    public function definition(): array
    {
        $faker = fake();
        return [
            'email' => $faker->unique()->safeEmail(),
            'username' => $faker->unique()->userName(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('123456789'),
            'role' => $faker->randomElement([
                'super_admin',
                'admin',
                'user',
            ]),
            'remember_token' => Str::random(10),
            'no_hp' => $faker->unique()->numerify('08##########'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
