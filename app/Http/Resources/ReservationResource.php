<?php

namespace App\Http\Resources;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reservation
 */
class ReservationResource extends JsonResource
{
    /**
     * Status, price, calculated total paid and balance, the unit, its payments and key dates.
     * The buyer's name and email appear only when the user relation is loaded (the manager's view).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total_price' => $this->total_price,
            'total_paid' => $this->total_paid, // calculated, never stored (R3)
            'balance' => $this->balance,
            'house' => [
                'id' => $this->house->id,
                'block' => $this->house->block,
                'lot' => $this->house->lot,
                'house_type' => $this->house->houseType->name,
            ],
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at,
            'paid_at' => $this->paid_at,
            'cancelled_at' => $this->cancelled_at,
        ];
    }
}
