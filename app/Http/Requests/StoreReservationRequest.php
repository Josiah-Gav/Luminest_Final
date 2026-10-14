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
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fee.min' => 'The reservation fee must be at least ₱5,000.',
        ];
    }
}
