<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;
use Throwable;

/**
 * Google login. A full-page browser flow, so it uses the web session and always ends with a redirect to the React app.
 */
class GoogleAuthController extends Controller
{
    /** GET /auth/google/redirect: sends the browser to Google's consent screen. */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * GET /auth/google/callback: logs the user in (rules in GoogleAuthService) and returns to the
     * React app, or to /login?error=<code>. Raw exception text is never shown.
     */
    public function callback(Request $request, GoogleAuthService $google): RedirectResponse
    {
        if ($request->has('error')) {
            return $this->failed('cancelled'); // user pressed "Cancel" at Google
        }

        try {
            $user = $google->findOrCreateUser(Socialite::driver('google')->user());
        } catch (RuntimeException $e) {
            // Only our own codes pass through; other RuntimeExceptions (e.g. Socialite's) become 'failed'.
            return $this->failed($e->getMessage() === 'inactive' ? 'inactive' : 'failed');
        } catch (Throwable) {
            return $this->failed('failed');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(config('app.frontend_url').'/oauth/callback');
    }

    /** Back to the login page with a short error code the page turns into a friendly message. */
    private function failed(string $code): RedirectResponse
    {
        return redirect(config('app.frontend_url').'/login?error='.$code);
    }
}
