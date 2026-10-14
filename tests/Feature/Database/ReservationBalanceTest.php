<?php

use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('calculates the balance as total price minus the sum of payments (R3)', function () {
    $reservation = Reservation::factory()->create(['total_price' => 2_500_000]);
    Payment::factory()->for($reservation)->create(['amount' => 5_000]);
    Payment::factory()->for($reservation)->create(['amount' => 100_000]);

    expect($reservation->total_paid)->toBe(105_000)
        ->and($reservation->balance)->toBe(2_395_000);
});

it('has the full price as balance before any payment', function () {
    $reservation = Reservation::factory()->create(['total_price' => 1_500_000]);

    expect($reservation->total_paid)->toBe(0)
        ->and($reservation->balance)->toBe(1_500_000);
});

it('reuses a withSum total instead of running another query', function () {
    $reservation = Reservation::factory()->create(['total_price' => 1_500_000]);
    Payment::factory()->for($reservation)->create(['amount' => 50_000]);

    $loaded = Reservation::withSum('payments', 'amount')->find($reservation->id);

    DB::enableQueryLog();
    expect($loaded->balance)->toBe(1_450_000)
        ->and(DB::getQueryLog())->toBeEmpty();
});

it('numbers each receipt from the payment year and id', function () {
    $payment = Payment::factory()->create(['paid_at' => '2026-10-14 09:30:00']);

    expect($payment->receipt_number)
        ->toBe(sprintf('LUM-2026-%06d', $payment->id))
        ->and($payment->fresh()->receipt_number)->toBe($payment->receipt_number);
});
