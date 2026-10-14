<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

// Test-only route: it exists while these tests run and is never in routes/api.php.
beforeEach(function () {
    Route::middleware(['api', 'auth:sanctum', 'role:admin,property_manager'])
        ->get('/api/_test/back-office', fn () => ['ok' => true]);
});

it('lets in a user with one of the listed roles', function (Role $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->getJson('/api/_test/back-office')
        ->assertOk();
})->with([Role::Admin, Role::PropertyManager]);

it('answers any other role with 403 JSON', function (Role $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get('/api/_test/back-office')
        ->assertForbidden()
        ->assertJson(['message' => 'This action is unauthorized.']);
})->with([Role::MaintenanceStaff, Role::Tenant, Role::Prospect]);

it('rejects a guest before checking the role', function () {
    $this->getJson('/api/_test/back-office')->assertUnauthorized();
});
