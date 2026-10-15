<?php

namespace App\Http\Controllers\Api\PropertyManager;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The property manager's read-only view of every reservation (R7).
 */
class ReservationController extends Controller
{
    /** GET /api/manager/reservations: every reservation with buyer, unit and payments, newest first (read-only, R7). */
    public function index(): AnonymousResourceCollection
    {
        $reservations = Reservation::with(['user', 'house.houseType', 'payments'])
            ->withSum('payments', 'amount')
            ->latest()
            ->get();

        return ReservationResource::collection($reservations);
    }
}
