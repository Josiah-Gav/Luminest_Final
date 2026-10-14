<?php

namespace App\Services;

use App\Enums\MaintenanceStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * What happens when a maintenance staff member stops being active staff.
 */
class StaffService
{
    /**
     * Puts the staff member's open work back in the queue: accepted/in_progress → pending,
     * unassigned (the enum allows both). Finished work (completed/resolved) keeps its staff as history.
     *
     * Call it inside the same transaction as the change that ends their staff duty
     * (demote, deactivate, role change, delete).
     */
    public function releaseOpenRequests(User $staff): void
    {
        $staff->assignedRequests()
            ->whereIn('status', [MaintenanceStatus::Accepted, MaintenanceStatus::InProgress])
            ->get()
            ->each(function ($request) {
                if ($request->status->canTransitionTo(MaintenanceStatus::Pending)) {
                    $request->update(['status' => MaintenanceStatus::Pending, 'assigned_staff_id' => null]);
                }
            });
    }

    /**
     * The property manager removes the staff role: back to prospect, expertise cleared.
     */
    public function demote(User $staff): void
    {
        DB::transaction(function () use ($staff) {
            $this->releaseOpenRequests($staff);

            $staff->update(['role' => Role::Prospect, 'expertise' => null]);
        });
    }
}
