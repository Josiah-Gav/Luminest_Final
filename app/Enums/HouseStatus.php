<?php

namespace App\Enums;

/**
 * A unit's status is driven only by reservations (plan §1.2 R7):
 * reserve (R1), cancel (R6) and the final payment (R5).
 */
enum HouseStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Available => $next === self::Reserved,
            self::Reserved => in_array($next, [self::Available, self::Sold], true),
            self::Sold => false,
        };
    }
}
