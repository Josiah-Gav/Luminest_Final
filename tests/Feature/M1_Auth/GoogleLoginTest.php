<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

/** Makes Socialite return this fake Google profile; Google is never called. */
function fakeGoogle(array $overrides = []): void
{
    $profile = array_merge([
        'id' => 'google-123',
        'name' => 'Gina Google',
        'email' => 'gina@example.com',
        'avatar' => 'https://example.com/gina.png',
        'email_verified' => true,
    ], $overrides);

    $google = (new SocialiteUser)->setRaw($profile)->map($profile);

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($google);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

it('creates a prospect from a new Google user, logs them in and redirects to the app', function () {
    fakeGoogle();

    $this->get('/auth/google/callback')
        ->assertRedirect(config('app.frontend_url').'/oauth/callback');

    $user = User::where('email', 'gina@example.com')->firstOrFail();
    expect($user->role)->toBe(Role::Prospect)
        ->and($user->google_id)->toBe('google-123')
        ->and($user->avatar_url)->toBe('https://example.com/gina.png')
        ->and($user->password)->toBeNull()
        ->and($user->phone_number)->toBeNull();
    $this->assertAuthenticatedAs($user, 'web');
});

it('links an existing account by email without creating a duplicate', function () {
    $existing = User::factory()->create(['email' => 'gina@example.com']);
    fakeGoogle();

    $this->get('/auth/google/callback')
        ->assertRedirect(config('app.frontend_url').'/oauth/callback');

    expect(User::count())->toBe(1)
        ->and($existing->fresh()->google_id)->toBe('google-123');
    $this->assertAuthenticatedAs($existing, 'web');
});

it('does not log in an inactive user and redirects with an error', function () {
    $inactive = User::factory()->create(['email' => 'gina@example.com', 'is_active' => false]);
    fakeGoogle();

    $this->get('/auth/google/callback')
        ->assertRedirect(config('app.frontend_url').'/login?error=inactive');

    $this->assertGuest('web');
    expect($inactive->fresh()->google_id)->toBeNull();
});

it('touches nothing when Google reports the email as unverified', function () {
    $existing = User::factory()->create(['email' => 'gina@example.com']);
    fakeGoogle(['email_verified' => false]);

    $this->get('/auth/google/callback')
        ->assertRedirect(config('app.frontend_url').'/login?error=failed');

    $this->assertGuest('web');
    expect(User::count())->toBe(1)
        ->and($existing->fresh()->google_id)->toBeNull();
});

it('redirects with an error when the user cancels at Google', function () {
    $this->get('/auth/google/callback?error=access_denied')
        ->assertRedirect(config('app.frontend_url').'/login?error=cancelled');

    $this->assertGuest('web');
});

it('redirects with a short code, not the exception text, when Socialite fails', function () {
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andThrow(new RuntimeException('secret detail'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/callback')
        ->assertRedirect(config('app.frontend_url').'/login?error=failed');
});

it('rejects password login for a Google-only user with a 422, not a 500', function () {
    User::factory()->create(['email' => 'gina@example.com', 'password' => null, 'google_id' => 'google-123']);

    spa()->postJson('/api/login', ['email' => 'gina@example.com', 'password' => 'anything'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});
