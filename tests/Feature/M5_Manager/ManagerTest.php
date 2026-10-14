<?php

use App\Enums\HouseStatus;
use App\Enums\MaintenanceCategory;
use App\Enums\MaintenanceStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\House;
use App\Models\HouseType;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Phase 5 acceptance criteria for M5 (plan §7, API §5.6).

/** A reservation with payments of the given amounts (balance_after is not needed by these tests). */
function reservationWithPayments(ReservationStatus $status, int $totalPrice, array $amounts): Reservation
{
    $reservation = Reservation::factory()->create(['status' => $status, 'total_price' => $totalPrice]);
    foreach ($amounts as $amount) {
        Payment::factory()->for($reservation)->create(['amount' => $amount, 'balance_after' => 0]);
    }

    return $reservation;
}

it('adds up what was collected and what is still outstanding', function () {
    // Reserved: ₱1,000,000 with ₱105,000 paid → ₱895,000 outstanding.
    reservationWithPayments(ReservationStatus::Reserved, 1_000_000, [5_000, 100_000]);
    // Cancelled: the ₱5,000 fee is kept (non-refundable) but nothing is outstanding.
    reservationWithPayments(ReservationStatus::Cancelled, 2_000_000, [5_000]);
    // Paid: ₱500,000 fully paid.
    reservationWithPayments(ReservationStatus::Paid, 500_000, [5_000, 495_000]);

    $this->actingAs(userAs(Role::PropertyManager))->getJson('/api/manager/dashboard')
        ->assertOk()
        ->assertJsonPath('data.active_reservations', 1)
        ->assertJsonPath('data.total_collected', 610_000)
        ->assertJsonPath('data.total_outstanding', 895_000);
});

it('counts houses and maintenance requests by status and shows the 5 latest requests', function () {
    $type = HouseType::factory()->create();
    House::factory()->for($type)->count(2)->create();
    House::factory()->for($type)->create(['status' => HouseStatus::Reserved]);
    House::factory()->for($type)->create(['status' => HouseStatus::Sold]);
    MaintenanceRequest::factory()->count(6)->create();
    MaintenanceRequest::factory()->create(['status' => MaintenanceStatus::Completed, 'completed_at' => now()]);

    $this->actingAs(userAs(Role::PropertyManager))->getJson('/api/manager/dashboard')
        ->assertOk()
        ->assertJsonPath('data.houses.total', 4)
        ->assertJsonPath('data.houses.available', 2)
        ->assertJsonPath('data.houses.reserved', 1)
        ->assertJsonPath('data.houses.sold', 1)
        ->assertJsonPath('data.maintenance.pending', 6)
        ->assertJsonPath('data.maintenance.completed', 1)
        ->assertJsonPath('data.maintenance.in_progress', 0)
        ->assertJsonCount(5, 'data.recent_requests');
});

it('lists houses with a search and filters, paginated and read-only', function () {
    $aimee = HouseType::factory()->create(['slug' => 'aimee', 'name' => 'Aimee Rowhouse']);
    $armina = HouseType::factory()->create(['slug' => 'armina_single', 'name' => 'Armina Single']);
    $owner = userAs(Role::Tenant, ['name' => 'Tina Tenant']);
    House::factory()->for($aimee)->create(['block' => 9, 'lot' => 1, 'status' => HouseStatus::Sold, 'owner_id' => $owner->id]);
    House::factory()->for($aimee)->create(['block' => 9, 'lot' => 2]);
    House::factory()->for($armina)->create(['block' => 5, 'lot' => 3]);

    $this->actingAs(userAs(Role::PropertyManager));
    $this->getJson('/api/manager/houses')->assertOk()->assertJsonCount(3, 'data')->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page']]);
    $this->getJson('/api/manager/houses?status=sold')->assertJsonCount(1, 'data')->assertJsonPath('data.0.owner.name', 'Tina Tenant');
    $this->getJson('/api/manager/houses?house_type=armina_single')->assertJsonCount(1, 'data')->assertJsonPath('data.0.block', 5);
    $this->getJson('/api/manager/houses?search=tina')->assertJsonCount(1, 'data');

    // Status is driven by reservations (R7): there is no way to change it here.
    $this->patchJson('/api/manager/houses/'.House::first()->id, ['status' => 'sold'])->assertStatus(404);
});

it('lists tenants with their houses', function () {
    $tina = userAs(Role::Tenant, ['name' => 'Tina Tenant']);
    House::factory()->create(['status' => HouseStatus::Sold, 'owner_id' => $tina->id, 'block' => 9, 'lot' => 1]);
    userAs(Role::Tenant, ['name' => 'Tomas Tenant']);
    userAs(Role::Prospect, ['name' => 'Tia Prospect']);

    $this->actingAs(userAs(Role::PropertyManager));
    $this->getJson('/api/manager/tenants')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/manager/tenants?search=tina')->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.houses.0.block', 9);
});

it('shows each staff member with their active and completed request counts', function () {
    $plumber = userAs(Role::MaintenanceStaff, ['expertise' => MaintenanceCategory::Plumbing]);
    MaintenanceRequest::factory()->create(['status' => MaintenanceStatus::Accepted, 'assigned_staff_id' => $plumber->id]);
    MaintenanceRequest::factory()->create(['status' => MaintenanceStatus::InProgress, 'assigned_staff_id' => $plumber->id]);
    MaintenanceRequest::factory()->create(['status' => MaintenanceStatus::Resolved, 'assigned_staff_id' => $plumber->id, 'resolved_at' => now()]);

    $this->actingAs(userAs(Role::PropertyManager))->getJson('/api/manager/staff')
        ->assertOk()
        ->assertJsonPath('data.0.active_requests_count', 2)
        ->assertJsonPath('data.0.completed_requests_count', 1);
});

it('demotes staff to prospect and returns their open requests to pending, unassigned', function () {
    $plumber = userAs(Role::MaintenanceStaff, ['expertise' => MaintenanceCategory::Plumbing]);
    $accepted = MaintenanceRequest::factory()->create(['status' => MaintenanceStatus::Accepted, 'assigned_staff_id' => $plumber->id]);
    $busy = MaintenanceRequest::factory()->create(['status' => MaintenanceStatus::InProgress, 'assigned_staff_id' => $plumber->id]);
    $done = MaintenanceRequest::factory()->create(['status' => MaintenanceStatus::Completed, 'assigned_staff_id' => $plumber->id, 'completed_at' => now()]);

    $this->actingAs(userAs(Role::PropertyManager))
        ->deleteJson("/api/manager/staff/{$plumber->id}")
        ->assertNoContent();

    expect($plumber->fresh()->role)->toBe(Role::Prospect)
        ->and($plumber->fresh()->expertise)->toBeNull();
    foreach ([$accepted, $busy] as $request) {
        expect($request->fresh()->status)->toBe(MaintenanceStatus::Pending)
            ->and($request->fresh()->assigned_staff_id)->toBeNull();
    }
    expect($done->fresh()->status)->toBe(MaintenanceStatus::Completed)
        ->and($done->fresh()->assigned_staff_id)->toBe($plumber->id);
});

it('only demotes maintenance staff', function () {
    $tenant = userAs(Role::Tenant);

    $this->actingAs(userAs(Role::PropertyManager))
        ->deleteJson("/api/manager/staff/{$tenant->id}")
        ->assertNotFound();

    expect($tenant->fresh()->role)->toBe(Role::Tenant);
});

it('keeps the manager endpoints to property managers', function () {
    $plumber = userAs(Role::MaintenanceStaff, ['expertise' => MaintenanceCategory::Plumbing]);

    foreach ([Role::Admin, Role::Tenant, Role::MaintenanceStaff, Role::Prospect] as $role) {
        $this->actingAs(userAs($role));
        $this->getJson('/api/manager/dashboard')->assertForbidden();
        $this->getJson('/api/manager/houses')->assertForbidden();
        $this->getJson('/api/manager/tenants')->assertForbidden();
        $this->deleteJson("/api/manager/staff/{$plumber->id}")->assertForbidden();
    }

    expect($plumber->fresh()->role)->toBe(Role::MaintenanceStaff);
});
