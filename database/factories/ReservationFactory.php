<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\House;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Raw rows only: it does not touch the house status. Business flows go
 * through ReservationService (Phase 3).
 *
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'house_id' => House::factory(),
            'status' => ReservationStatus::Reserved,
            'total_price' => fn (array $attributes) => House::find($attributes['house_id'])->houseType->price,
        ];
    }
}
