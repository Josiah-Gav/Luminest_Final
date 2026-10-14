<?php

namespace App\Policies;

use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\Reservation;
use App\Models\User;

/**
 * Who may act on a reservation. Laravel finds this policy by name
 * (Reservation → ReservationPolicy). A false answer becomes a 403.
 */
class ReservationPolicy
{
    /** Only the owner, and only while it is still reserved. */
    public function pay(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id
            && $reservation->status === ReservationStatus::Reserved;
    }

    /** Only the owner, and only while it is still reserved (R6). */
    public function cancel(User $user, Reservation $reservation): bool
    {
        return $this->pay($user, $reservation);
    }

    /** The payer, the property manager or the admin (R9). */
    public function downloadReceipt(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id
            || in_array($user->role, [Role::PropertyManager, Role::Admin], true);
    }
}
