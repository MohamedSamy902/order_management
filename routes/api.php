<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// API V1 Routes
Route::prefix('v1')
    ->middleware(['api.version:v1', 'api.deprecated', 'api.secretkey'])
    ->group(base_path('routes/api_v1.php'));

// Default version redirect
Route::redirect('/', '/api/' . config('api.default_version'));
