<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Sanctum SPA (cookie) authentication: logging in stores the user id in the
 * Laravel session, and the session cookie identifies the browser afterwards.
 */
class AuthController extends Controller
{
    /** POST /api/register: creates a prospect account and logs it in at once. */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Self-registration always creates a prospect; only an admin can give other roles.
        $user = User::create([...$request->validated(), 'role' => Role::Prospect]);

        Auth::login($user);
        $request->session()->regenerate();

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /** POST /api/login: checks the credentials (throttled, inactive accounts refused) and starts a session. */
    public function login(LoginRequest $request): UserResource
    {
        $request->authenticate();

        // New session id after login, so an id planted before login is worthless (session fixation).
        $request->session()->regenerate();

        return new UserResource($request->user());
    }

    /** POST /api/logout: destroys the server-side session so the old cookie stops working. */
    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        // auth:sanctum cached the user for this request; without this, the new guest
        // session below would still be saved with this user's id in sessions.user_id.
        Auth::guard('sanctum')->forgetUser();

        // Delete the session on the server (the old cookie stops working) and issue a new CSRF token.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /** GET /api/user: returns the logged-in user; the React app calls it on load to restore the session. */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
