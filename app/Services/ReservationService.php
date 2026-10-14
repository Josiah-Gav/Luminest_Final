<?php

namespace App\Services;

use App\Enums\HouseStatus;
use App\Enums\PaymentType;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\House;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The reservation & purchase rules of plan §1.2 (R1–R6). Every method runs in
 * one DB transaction, so a failed rule leaves nothing half-saved.
 */
class ReservationService
{
    /** Smallest reservation fee and smallest instalment, in whole pesos. */
    public const MINIMUM_PAYMENT = 5_000;

    /**
     * R1–R3: reserve an available unit and pay the reservation fee in one go.
     */
    public function reserve(User $user, int $houseId, int $fee): Reservation
    {
        return DB::transaction(function () use ($user, $houseId, $fee) {
            // Row locks: a second reserve() of this house (or by this user) waits here
            // until we commit, then sees the new status, so double booking can't happen.
            $house = House::with('houseType')->lockForUpdate()->findOrFail($houseId);
            User::lockForUpdate()->find($user->id);

            if (! $house->status->canTransitionTo(HouseStatus::Reserved)) {
                $this->fail('house_id', 'This unit is no longer available.');
            }
            if ($user->reservations()->active()->exists()) {
                $this->fail('house_id', 'You already have an active reservation. Pay it off or cancel it first.');
            }

            $price = $house->houseType->price;
            if ($fee > $price) {
                $this->fail('fee', 'The fee cannot be more than the price of ₱'.number_format($price).'.');
            }

            $reservation = $user->reservations()->create([
                'house_id' => $house->id,
                'status' => ReservationStatus::Reserved,
                'total_price' => $price, // R3: price snapshot
            ]);
            $house->update(['status' => HouseStatus::Reserved]);

            $this->recordPayment($reservation, $fee, PaymentType::ReservationFee);

            return $reservation;
        });
    }

    /**
     * R4: pay an instalment. At least ₱5,000, or exactly the balance when less
     * than ₱5,000 is left, and never more than the balance.
     */
    public function pay(Reservation $reservation, int $amount): Reservation
    {
        return DB::transaction(function () use ($reservation, $amount) {
            // Lock so two payments at the same time can't both pass the balance check.
            $reservation = Reservation::lockForUpdate()->findOrFail($reservation->id);
            $balance = $reservation->balance;

            if (! $reservation->status->canTransitionTo(ReservationStatus::Paid)) {
                $this->fail('amount', 'This reservation no longer accepts payments.');
            }
            if ($amount > $balance) {
                $this->fail('amount', 'The amount cannot be more than the balance of ₱'.number_format($balance).'.');
            }
            if ($balance < self::MINIMUM_PAYMENT && $amount !== $balance) {
                $this->fail('amount', 'Less than ₱5,000 is left, so pay exactly ₱'.number_format($balance).'.');
            }
            if ($balance >= self::MINIMUM_PAYMENT && $amount < self::MINIMUM_PAYMENT) {
                $this->fail('amount', 'Each payment must be at least ₱5,000.');
            }

            $isFinal = $amount === $balance;
            $this->recordPayment($reservation, $amount, $isFinal ? PaymentType::Final : PaymentType::Instalment);

            return $reservation;
        });
    }

    /**
     * R6: cancel. The unit is free again; payments stay on record (non-refundable).
     */
    public function cancel(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation) {
            $reservation = Reservation::with('house')->lockForUpdate()->findOrFail($reservation->id);

            if (! $reservation->status->canTransitionTo(ReservationStatus::Cancelled)) {
                $this->fail('reservation', 'Only an active reservation can be cancelled.');
            }

            $reservation->update(['status' => ReservationStatus::Cancelled, 'cancelled_at' => now()]);
            $reservation->house->update(['status' => HouseStatus::Available]);

            return $reservation;
        });
    }

    /**
     * Saves one payment with its balance snapshot. When the balance reaches ₱0
     * the purchase completes (R5), also when the reservation fee was the full price.
     */
    private function recordPayment(Reservation $reservation, int $amount, PaymentType $type): void
    {
        $balanceAfter = $reservation->balance - $amount;

        $reservation->payments()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'paid_at' => now(),
        ]);

        if ($balanceAfter === 0) {
            $this->completePurchase($reservation);
        }
    }

    /**
     * R5: reservation → paid, house → sold to the buyer, prospect → tenant.
     */
    private function completePurchase(Reservation $reservation): void
    {
        $reservation->update(['status' => ReservationStatus::Paid, 'paid_at' => now()]);

        $reservation->house->update([
            'status' => HouseStatus::Sold,
            'owner_id' => $reservation->user_id,
            'purchased_at' => now(),
        ]);

        if ($reservation->user->role === Role::Prospect) {
            $reservation->user->update(['role' => Role::Tenant]);
        }
    }

    /**
     * Stops the transaction with a 422 that shows under the form field.
     */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
