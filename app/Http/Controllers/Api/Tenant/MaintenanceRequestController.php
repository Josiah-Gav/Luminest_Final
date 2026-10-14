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
    public function __construct(private MaintenanceService $maintenance) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = $request->user()->maintenanceRequests()
            ->with(['house.houseType', 'staff'])
            ->latest()
            ->get();

        return MaintenanceRequestResource::collection($requests);
    }

    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        $created = $this->maintenance->submit($request->user(), $request->validated());

        return $this->respond($created, 201);
    }

    public function resolve(MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        Gate::authorize('resolve', $maintenanceRequest);

        return $this->respond($this->maintenance->resolve($maintenanceRequest), 200);
    }

    private function respond(MaintenanceRequest $request, int $status): JsonResponse
    {
        $request->load(['house.houseType', 'staff']);

        return (new MaintenanceRequestResource($request))->response()->setStatusCode($status);
    }
}
