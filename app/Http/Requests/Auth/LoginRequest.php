<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Failed attempts allowed per email + IP before a one-minute lockout. */
    private const MAX_ATTEMPTS = 5;

    /**
     * Email and password are required; whether they are correct is checked in authenticate().
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Logs the user in, or throws: 429 when locked out, 422 for wrong
     * credentials or an inactive account.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Check the password without logging in yet, so an inactive user never gets a session.
        if (! Auth::validate($this->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $user = Auth::getLastAttempted();

        // Only revealed after a correct password, so it can't be used to probe for accounts.
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Please contact the administrator.',
            ]);
        }

        Auth::login($user);
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Blocks the attempt with a 429 after 5 failures for this email + IP (brute-force protection).
     *
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ])->status(429);
    }

    /** The rate-limit bucket: lowercased email plus IP address, so one person's typos don't lock others out. */
    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
