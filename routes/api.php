<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::name('login')->post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/me', [AuthController::class, 'me'])
        ->middleware('abilities:'. TokenAbility::ACCESS_API->value)
        ->name('me');

    Route::get('/refresh', [AuthController::class, 'refreshToken'])
        ->middleware('abilities:'. TokenAbility::REFRESH_ACCESS_TOKEN->value)
        ->name('refresh_token');

    Route::delete('/logout', [AuthController::class, 'logout'])
        ->middleware('abilities:'. TokenAbility::ACCESS_API->value)
        ->name('logout');
});
