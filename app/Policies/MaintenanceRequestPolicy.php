<?php

namespace App\Policies;

use App\Models\MaintenanceRequest;
use App\Models\User;

/**
 * Who may act on a maintenance request. Staff and PM are limited by route role
 * middleware (and staff by only ever loading their own assignments), so the
 * only per-record rule left is ownership.
 */
class MaintenanceRequestPolicy
{
    /** Only the tenant who filed it. */
    public function resolve(User $user, MaintenanceRequest $request): bool
    {
        return $user->id === $request->tenant_id;
    }
}
