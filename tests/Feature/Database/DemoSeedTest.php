<?php

use App\Enums\HouseStatus;
use App\Enums\MaintenanceCategory;
use App\Enums\MaintenanceStatus;
use App\Enums\PaymentType;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\House;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Reservation;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// The on-demand demo data: `php artisan db:seed --class=DemoSeeder` after the base seed.
beforeEach(function () {
    $this->seed();
    $this->seed(DemoSeeder::class);
});

it('seeds reservations in all three statuses', function () {
    $statuses = Reservation::pluck('status')->unique()->sortBy->value->values()->all();

    expect($statuses)->toBe([ReservationStatus::Cancelled, ReservationStatus::Paid, ReservationStatus::Reserved]);
});

it('keeps every payment history consistent with the price (R3, R4, R5)', function () {
    foreach (Reservation::with('payments', 'house.houseType')->get() as $reservation) {
        $payments = $reservation->payments->sortBy(['paid_at', 'id'])->values();
        $running = $reservation->total_price;

        expect($reservation->total_price)->toBe($reservation->house->houseType->price)
            ->and($payments->first()->type)->toBe(PaymentType::ReservationFee)
            ->and($payments->first()->amount)->toBeGreaterThanOrEqual(5_000);

        foreach ($payments as $payment) {
            $running -= $payment->amount;
            expect($payment->balance_after)->toBe($running)
                ->and($payment->receipt_number)->toStartWith('LUM-');
        }

        expect($reservation->balance)->toBe($running);

        match ($reservation->status) {
            ReservationStatus::Paid => expect($reservation->balance)->toBe(0)
                ->and($payments->last()->type)->toBe(PaymentType::Final)
                ->and($reservation->paid_at)->not->toBeNull(),
            ReservationStatus::Cancelled => expect($reservation->balance)->toBeGreaterThan(0)
                ->and($reservation->cancelled_at)->not->toBeNull(),
            ReservationStatus::Reserved => expect($reservation->balance)->toBeGreaterThan(0),
        };
    }
});

it('keeps house status and ownership in line with reservations', function () {
    foreach (Reservation::active()->with('house')->get() as $reservation) {
        expect($reservation->house->status)->toBe(HouseStatus::Reserved)
            ->and($reservation->house->owner_id)->toBeNull();
    }

    foreach (Reservation::where('status', ReservationStatus::Paid)->with('house', 'user')->get() as $reservation) {
        expect($reservation->house->status)->toBe(HouseStatus::Sold)
            ->and($reservation->house->owner_id)->toBe($reservation->user_id)
            ->and($reservation->house->purchased_at)->not->toBeNull()
            ->and($reservation->user->role)->toBe(Role::Tenant);
    }

    // Every unit that isn't held by an active or paid reservation is free.
    expect(House::where('status', HouseStatus::Reserved)->count())->toBe(Reservation::active()->count())
        ->and(House::where('status', HouseStatus::Sold)->count())->toBe(Reservation::where('status', ReservationStatus::Paid)->count())
        ->and(House::available()->whereNotNull('owner_id')->count())->toBe(0);
});

it('gives no user more than one active reservation (R2)', function () {
    expect(Reservation::active()->selectRaw('user_id, count(*) as n')->groupBy('user_id')->having('n', '>', 1)->count())->toBe(0);
});

it('seeds maintenance requests in every status, assigned by expertise', function () {
    $requests = MaintenanceRequest::with('staff', 'house')->get();

    expect($requests->pluck('status')->unique()->count())->toBe(count(MaintenanceStatus::cases()));

    foreach ($requests as $request) {
        expect($request->house->owner_id)->toBe($request->tenant_id);

        if ($request->status === MaintenanceStatus::Pending) {
            expect($request->staff)->toBeNull();

            continue;
        }

        expect($request->staff->role)->toBe(Role::MaintenanceStaff)
            ->and($request->staff->expertise)->toBeIn([$request->category, MaintenanceCategory::General]);

        if (in_array($request->status, [MaintenanceStatus::Completed, MaintenanceStatus::Resolved], true)) {
            expect($request->completed_at)->not->toBeNull();
        }

        if ($request->status === MaintenanceStatus::Resolved) {
            expect($request->resolved_at)->not->toBeNull();
        }
    }
});

it('does nothing when run a second time', function () {
    $this->seed(DemoSeeder::class);

    expect(Reservation::count())->toBe(3)
        ->and(Payment::count())->toBe(7)
        ->and(MaintenanceRequest::count())->toBe(5);
});
