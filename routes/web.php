<?php

use App\Http\Controllers\AngularAppController;
use Illuminate\Support\Facades\Route;

Route::get('/doc/87e1ab37-81a5-434b-a9e0-e5e305c09100', function () {
    return view("scribe.index");
});

Route::get('{any}', [AngularAppController::class, 'index'])
    ->where('any', '^(?!api|docs|gp|storage)(?!.*\.\w{2,5}$).*');
