<?php

namespace App\Enums;

/**
 * SYSTEM_DOCUMENTATION §4.3 without the legacy shortcuts (defect #14):
 * staff can't work on an unassigned request or reopen a completed one.
 */
enum MaintenanceStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Resolved = 'resolved';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => $next === self::Accepted,                                 // PM assigns staff
            self::Accepted => in_array($next, [self::Pending, self::InProgress], true), // PM unassigns / staff starts
            self::InProgress => in_array($next, [self::Pending, self::Completed], true), // staff demoted (§5.6) / staff finishes
            self::Completed => $next === self::Resolved,                               // tenant confirms
            self::Resolved => false,
        };
    }
}
