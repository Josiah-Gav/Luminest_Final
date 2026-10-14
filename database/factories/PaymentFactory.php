<?php

namespace Database\Factories;

use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * A ₱5,000 payment; balance_after is the reservation's balance after it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'type' => PaymentType::ReservationFee,
            'amount' => 5_000,
            'balance_after' => fn (array $attributes) => Reservation::find($attributes['reservation_id'])->balance - $attributes['amount'],
            'method' => 'simulated',
            'paid_at' => now(),
        ];
    }
}
