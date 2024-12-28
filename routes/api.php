<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::name('login')->post('/login', [AuthController::class, 'login']);
// Route::post('/refresh', [AuthController::class, 'refreshToken']);

// Route::group(['middleware' => ['auth:api']], function () {
//     Route::get('/me', [AuthController::class, 'me']);
//     Route::post('/logout', [AuthController::class, 'logout']);
// });

// Route::name('test')->post('/tokens/create', function (Request $request) {

//     /** @var \App\Models\User $user */
//     $user = $request->user();

//     $token = $user->createToken($request->token_name, ['*'], now()->addSeconds(config('session.lifetime')));

//     return ['token' => $token->plainTextToken];
// });
