<?php

namespace App\Http\Controllers\Api\PropertyManager;

use App\Enums\MaintenanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignStaffRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * M4 for the property manager: the open queue, the resolved history, and assigning staff.
 */
class MaintenanceRequestController extends Controller
{
    public function __construct(private MaintenanceService $maintenance) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MaintenanceRequest::with(['house.houseType', 'tenant', 'staff'])->latest();

        // open = everything the tenant hasn't confirmed yet; closed = resolved (read-only history)
        $requests = $request->query('scope') === 'closed'
            ? $query->where('status', MaintenanceStatus::Resolved)->get()
            : $query->open()->get();

        return MaintenanceRequestResource::collection($requests);
    }

    public function assign(AssignStaffRequest $request, MaintenanceRequest $maintenanceRequest): MaintenanceRequestResource
    {
        $staff = $request->validated('staff_id') ? User::find($request->validated('staff_id')) : null;

        $assigned = $this->maintenance->assign($maintenanceRequest, $staff);

        return new MaintenanceRequestResource($assigned->load(['house.houseType', 'tenant', 'staff']));
    }
}
