<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\MaintenanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProgressRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * M4 for maintenance staff. Every query starts from the staff member's own
 * assignments, so a request assigned to someone else is simply not found (404).
 */
class MaintenanceRequestController extends Controller
{
    /** Receives MaintenanceService, which holds the status-transition rules. */
    public function __construct(private MaintenanceService $maintenance) {}

    /** GET /api/staff/maintenance-requests: the staff member's active work, or finished work with ?scope=history. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $mine = $request->user()->assignedRequests()->with(['house.houseType', 'tenant']);

        $requests = $request->query('scope') === 'history'
            ? $mine->closed()->latest('updated_at')->get()
            : $mine->whereIn('status', [MaintenanceStatus::Accepted, MaintenanceStatus::InProgress])->latest()->get();

        return MaintenanceRequestResource::collection($requests);
    }

    /** PATCH /api/staff/maintenance-requests/{id}: moves the request forward and saves notes (rules in MaintenanceService::updateProgress). */
    public function update(UpdateProgressRequest $request, int $id): MaintenanceRequestResource
    {
        $assigned = $request->user()->assignedRequests()->findOrFail($id);

        $updated = $this->maintenance->updateProgress(
            $assigned,
            MaintenanceStatus::from($request->validated('status')),
            $request->validated('resolution_notes'),
        );

        return new MaintenanceRequestResource($updated->load(['house.houseType', 'tenant']));
    }
}
