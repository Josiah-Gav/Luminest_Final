<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ends the session on the server, so the old cookie stops working', function () {
    $user = User::factory()->create(['email' => 'tina@example.com']);
    $cookie = config('session.cookie');

    $session = freshRequest()->postJson('/api/login', ['email' => 'tina@example.com', 'password' => 'password'])
        ->assertOk()
        ->getCookie($cookie)->getValue();

    // Each request finds the user through the session cookie alone.
    freshRequest([$cookie => $session])->getJson('/api/user')->assertOk();
    $this->assertDatabaseCount('sessions', 1);

    freshRequest([$cookie => $session])->postJson('/api/logout')->assertNoContent();
    $this->assertDatabaseMissing('sessions', ['id' => $session]);
    // The fresh guest session issued on logout must not be tagged with the user either.
    $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);

    // Replaying the old cookie (e.g. a stolen copy) no longer works.
    freshRequest([$cookie => $session])->getJson('/api/user')->assertUnauthorized();
});

it('rejects a guest', function () {
    spa()->postJson('/api/logout')->assertUnauthorized();
});
