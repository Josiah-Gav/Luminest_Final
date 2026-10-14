<?php

use App\Enums\Role;
use App\Models\User;
use Tests\TestCase;

/*
| Every test in tests/Feature runs on Laravel's TestCase, which boots the app
| and gives HTTP helpers like getJson(). Unit tests stay plain PHPUnit.
*/

pest()->extend(TestCase::class)
    ->in('Feature');

/**
 * A request as the React app sends it. Sanctum only starts a session (and so
 * can log someone in) for requests whose Origin is a stateful domain.
 */
function spa(): TestCase
{
    return test()->withHeader('Origin', config('app.frontend_url'));
}

/**
 * The next request as a new PHP process would see it: no user or session
 * kept in memory from earlier requests, so only the cookies sent along carry
 * the session. Sessions live in the database here, which survives the reset.
 */
function freshRequest(array $cookies = []): TestCase
{
    config(['session.driver' => 'database']);
    app('auth')->forgetGuards();
    app()->forgetInstance('auth.driver'); // the Guard singleton the session handler asks for user_id
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store'); // the session the auth guard reads; it would keep the previous request's login

    // JSON test requests only send cookies with withCredentials().
    return spa()->withCredentials()->withCookies($cookies);
}

/**
 * A user with the given role (plus attribute overrides), for tests of who may do what.
 */
function userAs(Role $role, array $attributes = []): User
{
    return User::factory()->create(['role' => $role, ...$attributes]);
}
