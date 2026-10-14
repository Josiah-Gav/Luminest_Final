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
    public function __construct(private StaffService $staff) {}

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
     * Demote: back to prospect, and their open requests return to the queue.
     */
    public function destroy(User $user): Response
    {
        abort_unless($user->role === Role::MaintenanceStaff, 404);

        $this->staff->demote($user);

        return response()->noContent();
    }
}
