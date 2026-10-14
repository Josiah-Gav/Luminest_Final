<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'tina@example.com']); // password: "password"
});

it('logs in with the right email and password', function () {
    spa()->postJson('/api/login', ['email' => 'tina@example.com', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.id', $this->user->id)
        ->assertJsonMissingPath('data.password');

    $this->assertAuthenticatedAs($this->user, 'web');
});

it('gives the browser a new session id and CSRF token on login (no session fixation)', function () {
    $cookie = config('session.cookie');
    $before = freshRequest()->get('/sanctum/csrf-cookie');
    $sessionBefore = $before->getCookie($cookie)->getValue();

    // Prove the cookie is honoured: the same session comes back, unchanged.
    expect(freshRequest([$cookie => $sessionBefore])->getJson('/api/user')->assertUnauthorized()
        ->getCookie($cookie)->getValue())->toBe($sessionBefore);

    $after = freshRequest([$cookie => $sessionBefore])
        ->postJson('/api/login', ['email' => 'tina@example.com', 'password' => 'password'])
        ->assertOk();

    expect($after->getCookie($cookie)->getValue())->not->toBe($sessionBefore)
        ->and($after->getCookie('XSRF-TOKEN')->getValue())->not->toBe($before->getCookie('XSRF-TOKEN')->getValue());
});

it('rejects a wrong password', function () {
    spa()->postJson('/api/login', ['email' => 'tina@example.com', 'password' => 'wrong-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'These credentials do not match our records.']);

    $this->assertGuest('web');
});

it('refuses an inactive account with a clear message', function () {
    $this->user->update(['is_active' => false]);

    spa()->postJson('/api/login', ['email' => 'tina@example.com', 'password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'This account has been deactivated. Please contact the administrator.']);

    $this->assertGuest('web');
});

it('locks the email out for a while after 5 failed attempts', function () {
    foreach (range(1, 5) as $attempt) {
        spa()->postJson('/api/login', ['email' => 'tina@example.com', 'password' => 'wrong-password'])
            ->assertUnprocessable();
    }

    // Even the right password is refused until the lock expires.
    spa()->postJson('/api/login', ['email' => 'tina@example.com', 'password' => 'password'])
        ->assertTooManyRequests()
        ->assertJsonValidationErrors('email');

    $this->assertGuest('web');
});

it('requires an email and a password', function () {
    spa()->postJson('/api/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});
