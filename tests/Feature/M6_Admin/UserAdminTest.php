<?php

use App\Enums\MaintenanceCategory;
use App\Enums\MaintenanceStatus;
use App\Enums\Role;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Phase 5 acceptance criteria for M6 (plan §7, API §5.7).

function newUserPayload(array $overrides = []): array
{
    return [
        'name' => 'New Person',
        'email' => 'new.person@example.com',
        'phone_number' => '0917 555 0100',
        'password' => 'secret-password',
        'role' => 'tenant',
        ...$overrides,
    ];
}

it('counts users by role and by active status on the dashboard', function () {
    userAs(Role::Tenant);
    userAs(Role::Tenant, ['is_active' => false]);
    userAs(Role::MaintenanceStaff, ['expertise' => MaintenanceCategory::General]);

    $this->actingAs(userAs(Role::Admin))->getJson('/api/admin/dashboard')
        ->assertOk()
        ->assertJsonPath('data.total', 4)
        ->assertJsonPath('data.active', 3)
        ->assertJsonPath('data.inactive', 1)
        ->assertJsonPath('data.by_role.tenant', 2)
        ->assertJsonPath('data.by_role.maintenance_staff', 1)
        ->assertJsonPath('data.by_role.prospect', 0);
});

it('lists users with a search and a role filter', function () {
    userAs(Role::Tenant, ['name' => 'Tina Tenant']);
    userAs(Role::Prospect, ['name' => 'Paolo Prospect']);

    $this->actingAs(userAs(Role::Admin, ['name' => 'Ada Admin']));
    $this->getJson('/api/admin/users')->assertOk()->assertJsonCount(3, 'data');
    $this->getJson('/api/admin/users?search=tina')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Tina Tenant');
    $this->getJson('/api/admin/users?role=prospect')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Paolo Prospect');
});

it('creates a user with any role and normalizes the phone', function () {
    $this->actingAs(userAs(Role::Admin))
        ->postJson('/api/admin/users', newUserPayload(['role' => 'property_manager']))
        ->assertCreated()
        ->assertJsonPath('data.role', 'property_manager')
        ->assertJsonPath('data.phone_number', '09175550100');

    $this->postJson('/api/admin/users', newUserPayload(['email' => 'bad@example.com', 'password' => '']))
        ->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('requires expertise for staff and clears it for every other role', function () {
    $this->actingAs(userAs(Role::Admin));

    $this->postJson('/api/admin/users', newUserPayload(['role' => 'maintenance_staff']))
        ->assertUnprocessable()->assertJsonValidationErrors('expertise');

    $this->postJson('/api/admin/users', newUserPayload(['role' => 'maintenance_staff', 'expertise' => 'plumbing']))
        ->assertCreated()->assertJsonPath('data.expertise', 'plumbing');

    $this->postJson('/api/admin/users', newUserPayload(['email' => 'b@example.com', 'phone_number' => '09175550101', 'role' => 'tenant', 'expertise' => 'plumbing']))
        ->assertCreated()->assertJsonPath('data.expertise', null);

    // Editing a staff member into a tenant clears the expertise too.
    $staff = userAs(Role::MaintenanceStaff, ['expertise' => MaintenanceCategory::Electrical]);
    $this->putJson("/api/admin/users/{$staff->id}", ['role' => 'tenant'])
        ->assertOk()->assertJsonPath('data.role', 'tenant')->assertJsonPath('data.expertise', null);

    // And turning someone into staff needs an expertise.
    $this->putJson("/api/admin/users/{$staff->id}", ['role' => 'maintenance_staff'])
        ->assertUnprocessable()->assertJsonValidationErrors('expertise');
});

it('updates a user and keeps the password when it is left out', function () {
    $user = userAs(Role::Tenant);
    $hash = $user->password;

    $this->actingAs(userAs(Role::Admin))
        ->putJson("/api/admin/users/{$user->id}", ['name' => 'Renamed', 'email' => $user->email])
        ->assertOk()->assertJsonPath('data.name', 'Renamed');

    expect($user->fresh()->password)->toBe($hash);
});

it('does not let an admin delete, deactivate or change the role of themselves', function () {
    $admin = userAs(Role::Admin);
    $this->actingAs($admin);

    $this->deleteJson("/api/admin/users/{$admin->id}")->assertUnprocessable();
    $this->putJson("/api/admin/users/{$admin->id}", ['is_active' => false])
        ->assertUnprocessable()->assertJsonValidationErrors('is_active');
    $this->putJson("/api/admin/users/{$admin->id}", ['role' => 'tenant'])
        ->assertUnprocessable()->assertJsonValidationErrors('role');

    expect($admin->fresh())->not->toBeNull()
        ->and($admin->fresh()->is_active)->toBeTrue()
        ->and($admin->fresh()->role)->toBe(Role::Admin);
});

it('refuses to delete a user with reservation history and tells the admin to deactivate', function () {
    $buyer = userAs(Role::Prospect);
    Reservation::factory()->create(['user_id' => $buyer->id]);

    $this->actingAs(userAs(Role::Admin))
        ->deleteJson("/api/admin/users/{$buyer->id}")
        ->assertUnprocessable()
        ->assertJsonPath('message', fn ($message) => str_contains(strtolower($message), 'deactivate'));

    expect(User::find($buyer->id))->not->toBeNull();

    $plain = userAs(Role::Prospect);
    $this->deleteJson("/api/admin/users/{$plain->id}")->assertNoContent();
    expect(User::find($plain->id))->toBeNull();
});

it('logs a user out at once when they are deactivated', function () {
    $user = userAs(Role::Tenant, ['email' => 'tina@example.com']);
    $cookie = config('session.cookie');

    $session = freshRequest()->postJson('/api/login', ['email' => 'tina@example.com', 'password' => 'password'])
        ->assertOk()->getCookie($cookie)->getValue();
    freshRequest([$cookie => $session])->getJson('/api/user')->assertOk();
    $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);

    freshRequest()->actingAs(userAs(Role::Admin))
        ->putJson("/api/admin/users/{$user->id}", ['is_active' => false])
        ->assertOk()->assertJsonPath('data.is_active', false);

    $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    freshRequest([$cookie => $session])->getJson('/api/user')->assertUnauthorized();
});

it('releases the open requests of staff who stop being staff (deactivate, change role, delete)', function () {
    $this->actingAs(userAs(Role::Admin));

    $actions = [
        fn (User $staff) => $this->putJson("/api/admin/users/{$staff->id}", ['is_active' => false])->assertOk(),
        fn (User $staff) => $this->putJson("/api/admin/users/{$staff->id}", ['role' => 'tenant'])->assertOk(),
        fn (User $staff) => $this->deleteJson("/api/admin/users/{$staff->id}")->assertNoContent(),
    ];

    foreach ($actions as $action) {
        $staff = userAs(Role::MaintenanceStaff, ['expertise' => MaintenanceCategory::Plumbing]);
        $busy = MaintenanceRequest::factory()->create(['status' => MaintenanceStatus::InProgress, 'assigned_staff_id' => $staff->id]);
        $accepted = MaintenanceRequest::factory()->create(['status' => MaintenanceStatus::Accepted, 'assigned_staff_id' => $staff->id]);
        $done = MaintenanceRequest::factory()->create(['status' => MaintenanceStatus::Completed, 'assigned_staff_id' => $staff->id, 'completed_at' => now()]);

        $action($staff);

        foreach ([$busy, $accepted] as $request) {
            expect($request->fresh()->status)->toBe(MaintenanceStatus::Pending)
                ->and($request->fresh()->assigned_staff_id)->toBeNull();
        }
        // Finished work stays on record. (After a delete the staff link is nulled by the FK, not by us.)
        expect($done->fresh()->status)->toBe(MaintenanceStatus::Completed);
    }
});

it('keeps the admin endpoints to admins', function () {
    $target = userAs(Role::Prospect);

    foreach ([Role::PropertyManager, Role::Tenant, Role::MaintenanceStaff, Role::Prospect] as $role) {
        $this->actingAs(userAs($role));
        $this->getJson('/api/admin/dashboard')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->postJson('/api/admin/users', newUserPayload())->assertForbidden();
        $this->getJson("/api/admin/users/{$target->id}")->assertForbidden();
        $this->putJson("/api/admin/users/{$target->id}", ['name' => 'Hacked'])->assertForbidden();
        $this->deleteJson("/api/admin/users/{$target->id}")->assertForbidden();
    }

    expect($target->fresh()->name)->not->toBe('Hacked');
});
