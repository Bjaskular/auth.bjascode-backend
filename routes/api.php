<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/me', [AuthController::class, 'me'])
        ->middleware('abilities:'. TokenAbility::ACCESS_API->value)
        ->name('me');

    Route::get('/authorize', [AuthController::class, 'authorize'])
        ->middleware('abilities:'. TokenAbility::ACCESS_API->value)
        ->name('authorize');

    Route::get('/refresh', [AuthController::class, 'refreshToken'])
        ->middleware('abilities:'. TokenAbility::REFRESH_ACCESS_TOKEN->value)
        ->name('refresh_token');

    Route::delete('/logout', [AuthController::class, 'logout'])
        ->middleware('abilities:'. TokenAbility::ACCESS_API->value)
        ->name('logout');
});
