<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(); // password: "password"
});

it('changes the password', function () {
    $this->actingAs($this->user)->putJson('/api/profile/password', [
        'current_password' => 'password',
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ])->assertNoContent();

    expect(Hash::check('new-secret-password', $this->user->fresh()->password))->toBeTrue();
});

it('requires the current password', function () {
    $this->actingAs($this->user)->putJson('/api/profile/password', [
        'current_password' => 'not-my-password',
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('current_password');

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

it('requires the new password to be confirmed', function () {
    $this->actingAs($this->user)->putJson('/api/profile/password', [
        'current_password' => 'password',
        'password' => 'new-secret-password',
        'password_confirmation' => 'different-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

it('rejects a guest', function () {
    $this->putJson('/api/profile/password', [])->assertUnauthorized();
});
