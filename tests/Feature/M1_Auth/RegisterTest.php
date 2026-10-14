<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function registration(array $overrides = []): array
{
    return array_merge([
        'name' => 'Maria Santos',
        'email' => 'maria@example.com',
        'phone_number' => '+63 917 123 4567',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ], $overrides);
}

it('registers a prospect and logs them in', function () {
    spa()->postJson('/api/register', registration())
        ->assertCreated()
        ->assertJsonPath('data.email', 'maria@example.com')
        ->assertJsonPath('data.role', 'prospect')
        ->assertJsonMissingPath('data.password');

    $user = User::where('email', 'maria@example.com')->firstOrFail();
    expect($user->role)->toBe(Role::Prospect);
    $this->assertAuthenticatedAs($user, 'web');
});

it('stores the phone number in 09XXXXXXXXX format', function () {
    spa()->postJson('/api/register', registration())->assertCreated();

    expect(User::where('email', 'maria@example.com')->value('phone_number'))->toBe('09171234567');
});

it('always creates a prospect, even when another role is sent', function () {
    spa()->postJson('/api/register', registration(['role' => 'admin']))
        ->assertCreated()
        ->assertJsonPath('data.role', 'prospect');
});

it('rejects an email that is already registered', function () {
    User::factory()->create(['email' => 'maria@example.com']);

    spa()->postJson('/api/register', registration())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
    $this->assertGuest('web');
});

it('rejects a phone number that is already registered in another format', function () {
    User::factory()->create(['phone_number' => '09171234567']);

    spa()->postJson('/api/register', registration(['phone_number' => '639171234567']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone_number');
});

it('rejects a phone number that is not a mobile number', function () {
    spa()->postJson('/api/register', registration(['phone_number' => '12345']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone_number');
});

it('rejects a password confirmation that does not match', function () {
    spa()->postJson('/api/register', registration(['password_confirmation' => 'something-else']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

it('requires every field', function () {
    spa()->postJson('/api/register', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'phone_number', 'password']);
});
