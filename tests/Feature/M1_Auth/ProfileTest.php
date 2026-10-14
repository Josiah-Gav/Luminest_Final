<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Tina Tenant',
        'email' => 'tina@example.com',
        'phone_number' => '09170000005',
    ]);
});

it('updates name, email and phone, normalizing the phone', function () {
    $this->actingAs($this->user)->putJson('/api/profile', [
        'name' => 'Tina Reyes',
        'email' => 'tina.reyes@example.com',
        'phone_number' => '+63 918 765 4321',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Tina Reyes')
        ->assertJsonPath('data.phone_number', '09187654321');

    expect($this->user->fresh()->email)->toBe('tina.reyes@example.com');
});

it('lets users keep their own email and phone', function () {
    $this->actingAs($this->user)->putJson('/api/profile', [
        'name' => 'Tina Reyes',
        'email' => 'tina@example.com',
        'phone_number' => '0917-000-0005',
    ])->assertOk();
});

it('rejects an email or phone that belongs to someone else', function () {
    User::factory()->create(['email' => 'taken@example.com', 'phone_number' => '09991112222']);

    $this->actingAs($this->user)->putJson('/api/profile', [
        'name' => 'Tina Tenant',
        'email' => 'taken@example.com',
        'phone_number' => '+639991112222',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'phone_number']);
});

it('rejects a guest', function () {
    $this->putJson('/api/profile', ['name' => 'Nobody'])->assertUnauthorized();
});
