<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/reservations. The availability and price checks need a row lock,
 * so they happen in ReservationService::reserve().
 */
class StoreReservationRequest extends FormRequest
{
    /**
     * The house must exist and the fee must be a whole number of at least ₱5,000 (R1); availability is checked in ReservationService::reserve().
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'house_id' => ['required', 'integer', 'exists:houses,id'],
            'fee' => ['required', 'integer', 'min:5000'], // whole pesos (R8), R1 minimum
        ];
    }

    /**
     * Friendlier error text when the fee is below the minimum.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fee.min' => 'The reservation fee must be at least ₱5,000.',
        ];
    }
}
