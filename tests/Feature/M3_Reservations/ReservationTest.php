<?php

use App\Enums\HouseStatus;
use App\Enums\PaymentType;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\House;
use App\Models\HouseType;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Plan §1.2 rules and the Phase 3 acceptance criteria (plan §7).

function unitPriced(int $price): House
{
    return House::factory()->for(HouseType::factory()->state(['price' => $price]))->create();
}

function userWithRole(Role $role): User
{
    return User::factory()->create(['role' => $role]);
}

/** Reserves $house for $user through the API and returns the reservation id. */
function reserveVia(User $user, House $house, int $fee = 5_000): int
{
    return test()->actingAs($user)
        ->postJson('/api/reservations', ['house_id' => $house->id, 'fee' => $fee])
        ->assertCreated()
        ->json('data.id');
}

it('blocks double booking of the same unit (R1)', function () {
    $house = unitPriced(1_500_000);
    reserveVia(userWithRole(Role::Prospect), $house);

    $this->actingAs(userWithRole(Role::Prospect))
        ->postJson('/api/reservations', ['house_id' => $house->id, 'fee' => 5_000])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('house_id');

    expect(Reservation::count())->toBe(1);
});

it('does not let a prospect with an active reservation reserve a second unit (R2)', function () {
    $prospect = userWithRole(Role::Prospect);
    reserveVia($prospect, unitPriced(1_500_000));
    $second = unitPriced(1_500_000);

    $this->actingAs($prospect)
        ->postJson('/api/reservations', ['house_id' => $second->id, 'fee' => 5_000])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('house_id');

    expect($second->fresh()->status)->toBe(HouseStatus::Available)
        ->and(Reservation::count())->toBe(1);
});

it('rejects a reservation fee under ₱5,000', function () {
    $house = unitPriced(1_500_000);

    $this->actingAs(userWithRole(Role::Prospect))
        ->postJson('/api/reservations', ['house_id' => $house->id, 'fee' => 4_999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('fee');

    expect($house->fresh()->status)->toBe(HouseStatus::Available)
        ->and(Reservation::count())->toBe(0);
});

it('rejects an instalment under ₱5,000 unless it settles the balance (R4)', function () {
    $prospect = userWithRole(Role::Prospect);
    $id = reserveVia($prospect, unitPriced(12_000)); // balance 7,000

    $this->postJson("/api/reservations/{$id}/payments", ['amount' => 4_000])
        ->assertUnprocessable()->assertJsonValidationErrors('amount');

    $this->postJson("/api/reservations/{$id}/payments", ['amount' => 5_000])
        ->assertCreated()->assertJsonPath('data.balance', 2_000);

    // Below ₱5,000 left: only the exact balance is accepted.
    $this->postJson("/api/reservations/{$id}/payments", ['amount' => 1_000])
        ->assertUnprocessable()->assertJsonValidationErrors('amount');

    $this->postJson("/api/reservations/{$id}/payments", ['amount' => 2_000])
        ->assertCreated()->assertJsonPath('data.balance', 0);
});

it('rejects an overpayment', function () {
    $prospect = userWithRole(Role::Prospect);
    $id = reserveVia($prospect, unitPriced(1_500_000));

    $this->postJson("/api/reservations/{$id}/payments", ['amount' => 1_495_001])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    expect(Reservation::find($id)->balance)->toBe(1_495_000);
});

it('completes the purchase on the final payment (R5)', function () {
    $prospect = userWithRole(Role::Prospect);
    $house = unitPriced(1_500_000);
    $id = reserveVia($prospect, $house);

    $this->postJson("/api/reservations/{$id}/payments", ['amount' => 1_495_000])
        ->assertCreated()
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.balance', 0);

    $house->refresh();
    expect(Reservation::find($id)->paid_at)->not->toBeNull()
        ->and($house->status)->toBe(HouseStatus::Sold)
        ->and($house->owner_id)->toBe($prospect->id)
        ->and($house->purchased_at)->not->toBeNull()
        ->and($prospect->fresh()->role)->toBe(Role::Tenant)
        ->and(Payment::latest('id')->first()->type)->toBe(PaymentType::Final);
});

it('completes the purchase at once when the fee is the full price', function () {
    $prospect = userWithRole(Role::Prospect);
    $house = unitPriced(1_500_000);

    $this->actingAs($prospect)
        ->postJson('/api/reservations', ['house_id' => $house->id, 'fee' => 1_500_000])
        ->assertCreated()
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.balance', 0);

    expect($house->fresh()->status)->toBe(HouseStatus::Sold)
        ->and($house->fresh()->owner_id)->toBe($prospect->id)
        ->and($prospect->fresh()->role)->toBe(Role::Tenant)
        ->and(Payment::first()->type)->toBe(PaymentType::ReservationFee);
});

it('frees the unit on cancel and keeps the payments (R6)', function () {
    $prospect = userWithRole(Role::Prospect);
    $house = unitPriced(1_500_000);
    $id = reserveVia($prospect, $house);
    $this->postJson("/api/reservations/{$id}/payments", ['amount' => 10_000])->assertCreated();

    $this->postJson("/api/reservations/{$id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($house->fresh()->status)->toBe(HouseStatus::Available)
        ->and(Reservation::find($id)->status)->toBe(ReservationStatus::Cancelled)
        ->and(Payment::count())->toBe(2);
});

it('does not let a property manager create, pay or cancel reservations (R7)', function () {
    $house = unitPriced(1_500_000);
    $id = reserveVia(userWithRole(Role::Prospect), unitPriced(1_500_000));
    $this->actingAs(userWithRole(Role::PropertyManager));

    $this->postJson('/api/reservations', ['house_id' => $house->id, 'fee' => 5_000])->assertForbidden();
    $this->postJson("/api/reservations/{$id}/payments", ['amount' => 5_000])->assertForbidden();
    $this->postJson("/api/reservations/{$id}/cancel")->assertForbidden();

    expect(Reservation::find($id)->status)->toBe(ReservationStatus::Reserved);
});

it("does not let a prospect pay or cancel someone else's reservation", function () {
    $id = reserveVia(userWithRole(Role::Prospect), unitPriced(1_500_000));
    $this->actingAs(userWithRole(Role::Prospect));

    $this->postJson("/api/reservations/{$id}/payments", ['amount' => 5_000])->assertForbidden();
    $this->postJson("/api/reservations/{$id}/cancel")->assertForbidden();
});

it('serves the receipt as a PDF to the payer, the property manager and the admin only (R9)', function () {
    $prospect = userWithRole(Role::Prospect);
    $id = reserveVia($prospect, unitPriced(1_500_000));
    $payment = Payment::where('reservation_id', $id)->first();
    $url = "/api/payments/{$payment->id}/receipt";

    foreach ([$prospect, userWithRole(Role::PropertyManager), userWithRole(Role::Admin)] as $allowed) {
        $this->actingAs($allowed)->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    $this->actingAs(userWithRole(Role::Prospect))->getJson($url)->assertForbidden();
});

it('lists my own reservations for a prospect or tenant, not for other roles', function () {
    $prospect = userWithRole(Role::Prospect);
    reserveVia($prospect, unitPriced(1_500_000));
    reserveVia(userWithRole(Role::Prospect), unitPriced(1_500_000)); // someone else's

    $this->actingAs($prospect)->getJson('/api/reservations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.balance', 1_495_000);

    $this->actingAs(userWithRole(Role::Tenant))->getJson('/api/reservations')->assertOk();
    $this->actingAs(userWithRole(Role::MaintenanceStaff))->getJson('/api/reservations')->assertForbidden();
});

it('shows every reservation to the property manager only', function () {
    $buyer = userWithRole(Role::Prospect);
    reserveVia($buyer, unitPriced(1_500_000));

    $this->actingAs(userWithRole(Role::PropertyManager))->getJson('/api/manager/reservations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.email', $buyer->email);

    $this->actingAs($buyer)->getJson('/api/manager/reservations')->assertForbidden();
});
