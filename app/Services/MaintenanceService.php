<?php

namespace App\Services;

use App\Enums\MaintenanceCategory;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The maintenance lifecycle (SYSTEM_DOCUMENTATION §4.3):
 * pending → accepted (assign) → in_progress → completed → resolved (tenant confirms).
 * Every status change asks MaintenanceStatus::canTransitionTo() first.
 */
class MaintenanceService
{
    /**
     * Tenant files a request. The house is already checked to be theirs by StoreMaintenanceRequest.
     *
     * @param  array<string, mixed>  $data
     */
    public function submit(User $tenant, array $data): MaintenanceRequest
    {
        return $tenant->maintenanceRequests()->create([...$data, 'status' => MaintenanceStatus::Pending]);
    }

    /**
     * PM assigns staff (pending → accepted), swaps them (accepted stays accepted)
     * or unassigns with null (accepted → pending). Not allowed once work has started.
     */
    public function assign(MaintenanceRequest $request, ?User $staff): MaintenanceRequest
    {
        return DB::transaction(function () use ($request, $staff) {
            $request = MaintenanceRequest::lockForUpdate()->findOrFail($request->id);

            if (! in_array($request->status, [MaintenanceStatus::Pending, MaintenanceStatus::Accepted], true)) {
                $this->fail('staff_id', 'Staff can only be changed before work has started.');
            }

            if ($staff === null) {
                if (! $request->status->canTransitionTo(MaintenanceStatus::Pending)) {
                    $this->fail('staff_id', 'This request has no staff assigned.');
                }
                $request->update(['assigned_staff_id' => null, 'status' => MaintenanceStatus::Pending]);

                return $request;
            }

            // Expertise must match the category, or the staff member is a generalist.
            if ($staff->expertise !== $request->category && $staff->expertise !== MaintenanceCategory::General) {
                $this->fail('staff_id', "This staff member's expertise doesn't match the {$request->category->value} category.");
            }

            if ($request->status === MaintenanceStatus::Pending) {
                $request->status = MaintenanceStatus::Accepted; // pending → accepted
            }
            $request->assigned_staff_id = $staff->id;
            $request->save();

            return $request;
        });
    }

    /**
     * Staff moves an assigned request forward: accepted → in_progress → completed.
     */
    public function updateProgress(MaintenanceRequest $request, MaintenanceStatus $status, ?string $notes): MaintenanceRequest
    {
        return DB::transaction(function () use ($request, $status, $notes) {
            $request = MaintenanceRequest::lockForUpdate()->findOrFail($request->id);

            if (! $request->status->canTransitionTo($status)) {
                $this->fail('status', "A {$request->status->value} request can't be set to {$status->value}.");
            }

            $request->status = $status;
            $request->resolution_notes = $notes ?? $request->resolution_notes;
            if ($status === MaintenanceStatus::Completed) {
                $request->completed_at = now();
            }
            $request->save();

            return $request;
        });
    }

    /**
     * Tenant confirms the work is done: completed → resolved.
     */
    public function resolve(MaintenanceRequest $request): MaintenanceRequest
    {
        return DB::transaction(function () use ($request) {
            $request = MaintenanceRequest::lockForUpdate()->findOrFail($request->id);

            if (! $request->status->canTransitionTo(MaintenanceStatus::Resolved)) {
                $this->fail('status', 'Only a completed request can be marked as resolved.');
            }

            $request->update(['status' => MaintenanceStatus::Resolved, 'resolved_at' => now()]);

            return $request;
        });
    }

    /**
     * Stops with a 422 that shows under the form field.
     */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
