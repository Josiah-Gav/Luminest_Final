<?php

use Illuminate\Support\Facades\Route;

// The UI is the React app in frontend/; the backend only serves JSON.
Route::get('/', fn () => ['name' => config('app.name').' API']);
