<?php

use App\Enums\HouseStatus;
use App\Enums\MaintenanceCategory;
use App\Enums\MaintenanceStatus;
use App\Enums\Role;
use App\Models\House;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Phase 4 acceptance criteria (plan §7), API of plan §5.4–5.6, lifecycle of SYSTEM_DOCUMENTATION §4.3.

function maintenanceUser(Role $role, array $attributes = []): User
{
    return User::factory()->create(['role' => $role, ...$attributes]);
}

function staffWith(MaintenanceCategory $expertise, array $attributes = []): User
{
    return maintenanceUser(Role::MaintenanceStaff, ['expertise' => $expertise, ...$attributes]);
}

/** @return array{0: User, 1: House} a tenant and the house they own */
function tenantWithHouse(): array
{
    $tenant = maintenanceUser(Role::Tenant);
    $house = House::factory()->create(['status' => HouseStatus::Sold, 'owner_id' => $tenant->id]);

    return [$tenant, $house];
}

function requestOf(User $tenant, House $house, array $attributes = []): MaintenanceRequest
{
    return MaintenanceRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'house_id' => $house->id,
        'category' => MaintenanceCategory::Plumbing,
        ...$attributes,
    ]);
}

function newRequestPayload(House $house, array $overrides = []): array
{
    return [
        'title' => 'Kitchen sink is leaking',
        'description' => 'Water drips under the sink.',
        'category' => 'plumbing',
        'priority' => 'high',
        'house_id' => $house->id,
        ...$overrides,
    ];
}

it('runs the whole lifecycle: submit → assign → in progress → completed → resolved', function () {
    [$tenant, $house] = tenantWithHouse();
    $plumber = staffWith(MaintenanceCategory::Plumbing);
    $manager = maintenanceUser(Role::PropertyManager);

    $id = $this->actingAs($tenant)
        ->postJson('/api/tenant/maintenance-requests', newRequestPayload($house))
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->json('data.id');

    $this->actingAs($manager)
        ->patchJson("/api/manager/maintenance-requests/{$id}/assignment", ['staff_id' => $plumber->id])
        ->assertOk()
        ->assertJsonPath('data.status', 'accepted')
        ->assertJsonPath('data.staff.id', $plumber->id);

    $this->actingAs($plumber)
        ->patchJson("/api/staff/maintenance-requests/{$id}", ['status' => 'in_progress'])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress');

    $this->patchJson("/api/staff/maintenance-requests/{$id}", ['status' => 'completed', 'resolution_notes' => 'Replaced the pipe.'])
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.resolution_notes', 'Replaced the pipe.');
    expect(MaintenanceRequest::find($id)->completed_at)->not->toBeNull();

    $this->actingAs($tenant)
        ->postJson("/api/tenant/maintenance-requests/{$id}/resolve")
        ->assertOk()
        ->assertJsonPath('data.status', 'resolved');
    expect(MaintenanceRequest::find($id)->resolved_at)->not->toBeNull();
});

it('does not let a tenant submit for a house they do not own', function () {
    [$tenant] = tenantWithHouse();
    $someoneElsesHouse = House::factory()->create(['status' => HouseStatus::Sold, 'owner_id' => maintenanceUser(Role::Tenant)->id]);

    $this->actingAs($tenant)
        ->postJson('/api/tenant/maintenance-requests', newRequestPayload($someoneElsesHouse))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('house_id');

    expect(MaintenanceRequest::count())->toBe(0);
});

it('validates the new request', function () {
    [$tenant, $house] = tenantWithHouse();

    $this->actingAs($tenant)
        ->postJson('/api/tenant/maintenance-requests', newRequestPayload($house, ['category' => 'gardening', 'title' => '']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category', 'title']);
});

it('lists only my own requests and only my own houses to a tenant', function () {
    [$tenant, $house] = tenantWithHouse();
    [$other, $otherHouse] = tenantWithHouse();
    requestOf($tenant, $house);
    requestOf($other, $otherHouse);

    $this->actingAs($tenant)->getJson('/api/tenant/maintenance-requests')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/tenant/houses')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $house->id);
});

it('lets a tenant resolve only a completed request that is theirs', function () {
    [$tenant, $house] = tenantWithHouse();

    foreach ([MaintenanceStatus::Pending, MaintenanceStatus::InProgress] as $status) {
        $request = requestOf($tenant, $house, ['status' => $status]);

        $this->actingAs($tenant)->postJson("/api/tenant/maintenance-requests/{$request->id}/resolve")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
        expect($request->fresh()->status)->toBe($status);
    }

    $completed = requestOf($tenant, $house, ['status' => MaintenanceStatus::Completed, 'completed_at' => now()]);
    $this->actingAs(maintenanceUser(Role::Tenant))
        ->postJson("/api/tenant/maintenance-requests/{$completed->id}/resolve")
        ->assertForbidden();
});

it('lets staff see and update only requests assigned to them', function () {
    [$tenant, $house] = tenantWithHouse();
    $plumber = staffWith(MaintenanceCategory::Plumbing);
    $other = staffWith(MaintenanceCategory::Plumbing);
    $request = requestOf($tenant, $house, ['status' => MaintenanceStatus::Accepted, 'assigned_staff_id' => $plumber->id]);

    $this->actingAs($plumber)->getJson('/api/staff/maintenance-requests?scope=active')
        ->assertOk()->assertJsonCount(1, 'data');

    $this->actingAs($other)->getJson('/api/staff/maintenance-requests?scope=active')
        ->assertOk()->assertJsonCount(0, 'data');
    $this->patchJson("/api/staff/maintenance-requests/{$request->id}", ['status' => 'in_progress'])
        ->assertNotFound();

    expect($request->fresh()->status)->toBe(MaintenanceStatus::Accepted);
});

it('splits the staff list into active and history', function () {
    [$tenant, $house] = tenantWithHouse();
    $plumber = staffWith(MaintenanceCategory::Plumbing);
    requestOf($tenant, $house, ['status' => MaintenanceStatus::InProgress, 'assigned_staff_id' => $plumber->id]);
    requestOf($tenant, $house, ['status' => MaintenanceStatus::Completed, 'assigned_staff_id' => $plumber->id, 'completed_at' => now()]);

    $this->actingAs($plumber);
    $this->getJson('/api/staff/maintenance-requests?scope=active')->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'in_progress');
    $this->getJson('/api/staff/maintenance-requests?scope=history')->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'completed');
});

it('does not let staff skip a step or reopen a completed request', function () {
    [$tenant, $house] = tenantWithHouse();
    $plumber = staffWith(MaintenanceCategory::Plumbing);
    $accepted = requestOf($tenant, $house, ['status' => MaintenanceStatus::Accepted, 'assigned_staff_id' => $plumber->id]);
    $completed = requestOf($tenant, $house, ['status' => MaintenanceStatus::Completed, 'assigned_staff_id' => $plumber->id, 'completed_at' => now()]);

    $this->actingAs($plumber);
    $this->patchJson("/api/staff/maintenance-requests/{$accepted->id}", ['status' => 'completed'])
        ->assertUnprocessable()->assertJsonValidationErrors('status');
    $this->patchJson("/api/staff/maintenance-requests/{$completed->id}", ['status' => 'in_progress'])
        ->assertUnprocessable()->assertJsonValidationErrors('status');
});

it('assigns only staff whose expertise matches the category, or general staff', function () {
    [$tenant, $house] = tenantWithHouse();
    $manager = maintenanceUser(Role::PropertyManager);
    $electrician = staffWith(MaintenanceCategory::Electrical);
    $generalist = staffWith(MaintenanceCategory::General);
    $request = requestOf($tenant, $house, ['category' => MaintenanceCategory::Carpentry]);
    $url = "/api/manager/maintenance-requests/{$request->id}/assignment";

    $this->actingAs($manager)->patchJson($url, ['staff_id' => $electrician->id])
        ->assertUnprocessable()->assertJsonValidationErrors('staff_id');
    expect($request->fresh()->status)->toBe(MaintenanceStatus::Pending);

    $this->patchJson($url, ['staff_id' => $generalist->id])
        ->assertOk()->assertJsonPath('data.status', 'accepted');
});

it('assigns only active maintenance staff', function () {
    [$tenant, $house] = tenantWithHouse();
    $manager = maintenanceUser(Role::PropertyManager);
    $request = requestOf($tenant, $house);
    $url = "/api/manager/maintenance-requests/{$request->id}/assignment";

    $this->actingAs($manager);
    $this->patchJson($url, ['staff_id' => staffWith(MaintenanceCategory::Plumbing, ['is_active' => false])->id])
        ->assertUnprocessable()->assertJsonValidationErrors('staff_id');
    $this->patchJson($url, ['staff_id' => $tenant->id])
        ->assertUnprocessable()->assertJsonValidationErrors('staff_id');
});

it('allows assigning and unassigning only before work has started', function () {
    [$tenant, $house] = tenantWithHouse();
    $manager = maintenanceUser(Role::PropertyManager);
    $first = staffWith(MaintenanceCategory::Plumbing);
    $second = staffWith(MaintenanceCategory::Plumbing);
    $request = requestOf($tenant, $house);
    $url = "/api/manager/maintenance-requests/{$request->id}/assignment";

    $this->actingAs($manager);
    $this->patchJson($url, ['staff_id' => $first->id])->assertOk()->assertJsonPath('data.status', 'accepted');
    // Reassigning while accepted swaps the staff and keeps the status.
    $this->patchJson($url, ['staff_id' => $second->id])->assertOk()
        ->assertJsonPath('data.status', 'accepted')->assertJsonPath('data.staff.id', $second->id);
    // Unassigning while accepted puts it back in the queue.
    $this->patchJson($url, ['staff_id' => null])->assertOk()
        ->assertJsonPath('data.status', 'pending')->assertJsonPath('data.staff', null);

    foreach ([MaintenanceStatus::InProgress, MaintenanceStatus::Completed, MaintenanceStatus::Resolved] as $status) {
        $busy = requestOf($tenant, $house, ['status' => $status, 'assigned_staff_id' => $first->id]);
        $busyUrl = "/api/manager/maintenance-requests/{$busy->id}/assignment";

        $this->patchJson($busyUrl, ['staff_id' => $second->id])->assertUnprocessable()->assertJsonValidationErrors('staff_id');
        $this->patchJson($busyUrl, ['staff_id' => null])->assertUnprocessable()->assertJsonValidationErrors('staff_id');
        expect($busy->fresh()->assigned_staff_id)->toBe($first->id);
    }
});

it('shows the manager the open queue and the resolved history, plus assignable staff', function () {
    [$tenant, $house] = tenantWithHouse();
    requestOf($tenant, $house, ['status' => MaintenanceStatus::Pending]);
    requestOf($tenant, $house, ['status' => MaintenanceStatus::Completed, 'completed_at' => now()]);
    requestOf($tenant, $house, ['status' => MaintenanceStatus::Resolved, 'resolved_at' => now()]);
    staffWith(MaintenanceCategory::Plumbing);
    staffWith(MaintenanceCategory::Electrical, ['is_active' => false]);

    $this->actingAs(maintenanceUser(Role::PropertyManager));
    $this->getJson('/api/manager/maintenance-requests?scope=open')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/manager/maintenance-requests?scope=closed')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/manager/staff')->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'expertise']]])
        ->assertJsonMissingPath('data.0.email');
});

it('keeps each endpoint group to its own role', function () {
    [$tenant, $house] = tenantWithHouse();
    $request = requestOf($tenant, $house);
    $tenantUrls = ['/api/tenant/maintenance-requests', '/api/tenant/houses'];
    $staffUrl = '/api/staff/maintenance-requests';
    $managerUrls = ['/api/manager/maintenance-requests', '/api/manager/staff'];

    // Staff and manager can't use the tenant endpoints.
    foreach ([Role::MaintenanceStaff, Role::PropertyManager] as $role) {
        $this->actingAs(maintenanceUser($role));
        foreach ($tenantUrls as $url) {
            $this->getJson($url)->assertForbidden();
        }
        $this->postJson('/api/tenant/maintenance-requests', newRequestPayload($house))->assertForbidden();
        $this->postJson("/api/tenant/maintenance-requests/{$request->id}/resolve")->assertForbidden();
    }

    // Tenant and manager can't use the staff endpoints.
    foreach ([Role::Tenant, Role::PropertyManager] as $role) {
        $this->actingAs(maintenanceUser($role));
        $this->getJson($staffUrl)->assertForbidden();
        $this->patchJson("{$staffUrl}/{$request->id}", ['status' => 'in_progress'])->assertForbidden();
    }

    // Tenant and staff can't use the manager endpoints.
    foreach ([Role::Tenant, Role::MaintenanceStaff] as $role) {
        $this->actingAs(maintenanceUser($role));
        foreach ($managerUrls as $url) {
            $this->getJson($url)->assertForbidden();
        }
        $this->patchJson("/api/manager/maintenance-requests/{$request->id}/assignment", ['staff_id' => null])->assertForbidden();
    }
});
