<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

/**
 * Turns a Google profile into a Luminest user: find by google_id, else link by email, else create a prospect.
 */
class GoogleAuthService
{
    /**
     * Returns the user for this Google profile, or throws RuntimeException with a short error code
     * ('failed' or 'inactive') that the controller puts in the redirect. Nothing is saved on a refusal.
     */
    public function findOrCreateUser(SocialiteUser $google): User
    {
        // An unverified Google email could belong to someone else, so it must never link or create an account.
        $verified = filter_var($google->user['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (! $google->getEmail() || ! $verified) {
            throw new RuntimeException('failed');
        }

        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $google->getEmail())->first();

        if ($user && ! $user->is_active) {
            throw new RuntimeException('inactive');
        }

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $google->getId()]); // link the existing account
            }

            return $user;
        }

        // Same rule as self-registration: always a prospect.
        return User::create([
            'name' => $google->getName() ?: $google->getEmail(),
            'email' => $google->getEmail(),
            'google_id' => $google->getId(),
            'avatar_url' => $google->getAvatar(),
            'password' => null,
            'phone_number' => null,
            'role' => Role::Prospect,
        ]);
    }
}
