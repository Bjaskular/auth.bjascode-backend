<?php

use App\Enums\AuthCookieName;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::name('login')->post('/login', [AuthController::class, 'login']);
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/me', [AuthController::class, 'me'])->name('me');

    Route::get('/refresh', [AuthController::class, 'refreshToken'])
        ->middleware('abilities:'. AuthCookieName::REFRESH->value)
        ->name('refresh_token');

    Route::delete('/logout', [AuthController::class, 'logout'])
        ->middleware('abilities:'. AuthCookieName::API_ACCESS->value)
        ->name('logout');
});
