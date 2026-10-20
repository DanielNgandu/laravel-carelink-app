<?php
use App\Http\Controllers\FollowUpController;
use Illuminate\Support\Facades\Route;
Route::middleware(['clinic.token', 'throttle:120,1'])->group(function () {
    Route::get('/follow-up', [FollowUpController::class, 'index']);
    Route::post('/follow-up/{patient}/contacted', [FollowUpController::class, 'contacted']);
});
