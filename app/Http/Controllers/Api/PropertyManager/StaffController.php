<?php

namespace App\Http\Controllers\Api\PropertyManager;

use App\Enums\MaintenanceStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\StaffResource;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Active maintenance staff: the assign dropdown and the Staff page.
 */
class StaffController extends Controller
{
    /** Receives StaffService, which releases a demoted staff member's open work. */
    public function __construct(private StaffService $staff) {}

    /** GET /api/manager/staff: active maintenance staff by name, with counts of their active and finished requests. */
    public function index(): AnonymousResourceCollection
    {
        $staff = User::staff()
            ->where('is_active', true)
            ->withCount([
                'assignedRequests as active_requests_count' => fn ($query) => $query->whereIn('status', [MaintenanceStatus::Accepted, MaintenanceStatus::InProgress]),
                'assignedRequests as completed_requests_count' => fn ($query) => $query->closed(),
            ])
            ->orderBy('name')
            ->get();

        return StaffResource::collection($staff);
    }

    /**
     * DELETE /api/manager/staff/{id}: demotes the staff member to prospect and returns their open
     * requests to the queue (StaffService::demote). A user who is not staff gives 404.
     */
    public function destroy(User $user): Response
    {
        abort_unless($user->role === Role::MaintenanceStaff, 404);

        $this->staff->demote($user);

        return response()->noContent();
    }
}
