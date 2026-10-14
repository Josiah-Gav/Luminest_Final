<?php

namespace App\Enums;

/**
 * Plan §1.2: reserved → paid (balance reaches ₱0, R5) or reserved → cancelled (R6).
 * Paid and cancelled are final.
 */
enum ReservationStatus: string
{
    case Reserved = 'reserved';
    case Cancelled = 'cancelled';
    case Paid = 'paid';

    public function canTransitionTo(self $next): bool
    {
        return $this === self::Reserved
            && in_array($next, [self::Cancelled, self::Paid], true);
    }
}
