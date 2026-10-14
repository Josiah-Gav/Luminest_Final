<?php

namespace Database\Factories;

use App\Enums\HouseStatus;
use App\Models\House;
use App\Models\HouseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<House>
 */
class HouseFactory extends Factory
{
    /**
     * An available unit. A unique lot keeps (block, lot) unique.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'house_type_id' => HouseType::factory(),
            'block' => fake()->numberBetween(1, 15),
            'lot' => fake()->unique()->numberBetween(1, 999),
            'status' => HouseStatus::Available,
            'owner_id' => null,
            'purchased_at' => null,
        ];
    }
}
