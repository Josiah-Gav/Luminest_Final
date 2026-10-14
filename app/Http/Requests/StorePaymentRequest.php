<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/reservations/{id}/payments. The ₱5,000 minimum depends on the
 * remaining balance (R4), so ReservationService::pay() checks it.
 */
class StorePaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'], // whole pesos (R8)
        ];
    }
}
