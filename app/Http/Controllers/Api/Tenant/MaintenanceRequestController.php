<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * M4 for the tenant: file requests and confirm them as resolved. Rules are in MaintenanceService.
 */
class MaintenanceRequestController extends Controller
{
    /** Receives MaintenanceService, which holds the maintenance rules. */
    public function __construct(private MaintenanceService $maintenance) {}

    /** GET /api/tenant/maintenance-requests: the tenant's own requests with house and assigned staff, newest first. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = $request->user()->maintenanceRequests()
            ->with(['house.houseType', 'staff'])
            ->latest()
            ->get();

        return MaintenanceRequestResource::collection($requests);
    }

    /** POST /api/tenant/maintenance-requests: files a new request as pending (rules in MaintenanceService::submit). */
    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        $created = $this->maintenance->submit($request->user(), $request->validated());

        return $this->respond($created, 201);
    }

    /** POST /api/tenant/maintenance-requests/{id}/resolve: the owning tenant confirms completed work (completed → resolved). */
    public function resolve(MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        Gate::authorize('resolve', $maintenanceRequest);

        return $this->respond($this->maintenance->resolve($maintenanceRequest), 200);
    }

    /** Returns the request with its house and staff loaded, using the given HTTP status. */
    private function respond(MaintenanceRequest $request, int $status): JsonResponse
    {
        $request->load(['house.houseType', 'staff']);

        return (new MaintenanceRequestResource($request))->response()->setStatusCode($status);
    }
}
