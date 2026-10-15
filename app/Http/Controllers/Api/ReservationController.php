<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * M3 Reservation & Purchase for the buyer. The rules live in ReservationService.
 */
class ReservationController extends Controller
{
    /** Receives ReservationService, which holds all the reservation and payment rules. */
    public function __construct(private ReservationService $reservations) {}

    /** GET /api/reservations: the logged-in user's own reservations with payments, newest first. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reservations = $request->user()->reservations()
            ->with(['house.houseType', 'payments'])
            ->withSum('payments', 'amount')
            ->latest()
            ->get();

        return ReservationResource::collection($reservations);
    }

    /** POST /api/reservations: reserves a unit and pays the reservation fee (rules in ReservationService::reserve). */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservations->reserve(
            $request->user(),
            $request->integer('house_id'),
            $request->integer('fee'),
        );

        return $this->respond($reservation, 201);
    }

    /** POST /api/reservations/{id}/payments: pays an instalment; only the owner (rules in ReservationService::pay). */
    public function pay(StorePaymentRequest $request, Reservation $reservation): JsonResponse
    {
        Gate::authorize('pay', $reservation);

        $reservation = $this->reservations->pay($reservation, $request->integer('amount'));

        return $this->respond($reservation, 201);
    }

    /** POST /api/reservations/{id}/cancel: cancels an active reservation and frees the unit; only the owner (R6). */
    public function cancel(Reservation $reservation): JsonResponse
    {
        Gate::authorize('cancel', $reservation);

        $reservation = $this->reservations->cancel($reservation);

        return $this->respond($reservation, 200);
    }

    /**
     * The reservation as it is now in the database, with its payments.
     */
    private function respond(Reservation $reservation, int $status): JsonResponse
    {
        $reservation = $reservation->fresh(['house.houseType', 'payments']);

        return (new ReservationResource($reservation))->response()->setStatusCode($status);
    }
}
