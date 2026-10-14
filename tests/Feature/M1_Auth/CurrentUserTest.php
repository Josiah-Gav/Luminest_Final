<?php

use App\Enums\MaintenanceCategory;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the logged-in user without secrets', function () {
    $user = User::factory()->create([
        'role' => Role::MaintenanceStaff,
        'expertise' => MaintenanceCategory::Plumbing,
        'google_id' => '1234567890',
    ]);

    $this->actingAs($user)->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.role', 'maintenance_staff')
        ->assertJsonPath('data.expertise', 'plumbing')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonMissingPath('data.password')
        ->assertJsonMissingPath('data.remember_token')
        ->assertJsonMissingPath('data.google_id');
});

it('rejects a guest asking for the current user', function () {
    $this->getJson('/api/user')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('answers a guest with JSON 401 even without an Accept: application/json header', function () {
    $this->get('/api/user')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});
