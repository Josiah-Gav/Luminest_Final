<?php

namespace App\Http\Controllers\Api\PropertyManager;

use App\Enums\HouseStatus;
use App\Enums\MaintenanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\House;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * M5 dashboard numbers. Money is whole pesos (R8).
 */
class DashboardController extends Controller
{
    public function __invoke(): JsonResource
    {
        $houses = ['total' => House::count()];
        foreach (HouseStatus::cases() as $status) {
            $houses[$status->value] = House::where('status', $status)->count();
        }

        $maintenance = [];
        foreach (MaintenanceStatus::cases() as $status) {
            $maintenance[$status->value] = MaintenanceRequest::where('status', $status)->count();
        }

        // Collected = every payment ever made, including the kept fees of cancelled reservations (non-refundable, R6).
        // Outstanding = what the buyers who are still paying (reserved) have left to pay.
        $collected = (int) Payment::sum('amount');
        $paidByActive = (int) Payment::whereHas('reservation', fn ($query) => $query->active())->sum('amount');

        return new JsonResource([
            'houses' => $houses,
            'maintenance' => $maintenance,
            'active_reservations' => Reservation::active()->count(),
            'total_collected' => $collected,
            'total_outstanding' => (int) Reservation::active()->sum('total_price') - $paidByActive,
            'recent_requests' => MaintenanceRequestResource::collection(
                MaintenanceRequest::with(['house.houseType', 'tenant', 'staff'])->latest()->take(5)->get()
            )->resolve(),
        ]);
    }
}
