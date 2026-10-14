<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware "role", e.g. ->middleware('role:admin,property_manager').
 * Runs after auth:sanctum, so there is always a user here.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Role::from() throws on a typo in a route definition instead of silently letting nobody (or everybody) in.
        $allowed = array_map(Role::from(...), $roles);

        // Same message as a failed policy check, so the frontend handles every 403 alike.
        abort_unless(in_array($request->user()?->role, $allowed, true), 403, 'This action is unauthorized.');

        return $next($request);
    }
}
