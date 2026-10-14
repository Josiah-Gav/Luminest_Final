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
    public function __construct(private ReservationService $reservations) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $reservations = $request->user()->reservations()
            ->with(['house.houseType', 'payments'])
            ->withSum('payments', 'amount')
            ->latest()
            ->get();

        return ReservationResource::collection($reservations);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservations->reserve(
            $request->user(),
            $request->integer('house_id'),
            $request->integer('fee'),
        );

        return $this->respond($reservation, 201);
    }

    public function pay(StorePaymentRequest $request, Reservation $reservation): JsonResponse
    {
        Gate::authorize('pay', $reservation);

        $reservation = $this->reservations->pay($reservation, $request->integer('amount'));

        return $this->respond($reservation, 201);
    }

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
