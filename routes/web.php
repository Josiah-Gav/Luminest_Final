<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

// The UI is the React app in frontend/; the backend only serves JSON.
Route::get('/', fn () => ['name' => config('app.name').' API']);

// M1 bonus: Google login (plan §5.1). Browser redirects, so they need the web session.
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
