<?php

namespace Database\Factories;

use App\Models\HouseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HouseType>
 */
class HouseTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->lastName().' Duplex';

        return [
            'slug' => str($name)->slug('_')->toString(),
            'name' => $name,
            'category' => 'Two-Storey Duplex',
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'badge' => null,
            'image_path' => null,
            'bedrooms' => fake()->numberBetween(1, 3),
            'bathrooms' => 1,
            'carports' => fake()->numberBetween(0, 1),
            'price' => fake()->numberBetween(15, 40) * 100_000,
        ];
    }
}
