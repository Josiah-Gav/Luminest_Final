<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Response;

/**
 * The logged-in user's own account, for every role.
 */
class ProfileController extends Controller
{
    /** PUT /api/profile: updates the user's own name, email and phone (validated by UpdateProfileRequest). */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $request->user()->update($request->validated());

        return new UserResource($request->user());
    }

    /** PUT /api/profile/password: changes the password after the current one is confirmed (UpdatePasswordRequest). */
    public function updatePassword(UpdatePasswordRequest $request): Response
    {
        // Hashed by the "hashed" cast on User.
        $request->user()->update(['password' => $request->validated('password')]);

        return response()->noContent();
    }
}
