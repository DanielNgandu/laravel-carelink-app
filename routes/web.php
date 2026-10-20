
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FollowUpController;

Route::get('/', function () {
    return redirect()->route('follow-up');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {

    Route::view('/follow-up', 'follow-up')
        ->name('follow-up')
        ->middleware('clinic.session');

    Route::middleware(['clinic.session', 'throttle:120,1'])
        ->group(function () {

            Route::get('/follow-up/data', [
                FollowUpController::class, 'index'
            ])->name('follow-up.data');

            Route::post('/follow-up/{patient}/contacted', [
                FollowUpController::class, 'contacted'
            ])->name('follow-up.contacted');
        });


        

    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');
});
