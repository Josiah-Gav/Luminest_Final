<?php

namespace Database\Factories;

use App\Enums\Role;
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
     * Define the model's default state: an active prospect, as after registering.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => '09'.fake()->unique()->numerify('#########'),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Prospect,
            'expertise' => null,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
