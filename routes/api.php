<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShebaController;

// Submit Sheba transfer request
Route::post( '/sheba', [ ShebaController::class, 'store' ] );

// Get all Sheba transfer requests
Route::get( '/sheba', [ ShebaController::class, 'index' ] );

// Confirm or cancel Sheba transfer request
Route::put( '/sheba/{id}', [ ShebaController::class, 'update' ] );
Route::post( '/sheba/{id}', [ ShebaController::class, 'update' ] ); // Alternative method for the same action

Route::get('/test', function () {
    return response()->json(['message' => 'API routes are working!']);
});

Route::post('/test', function () {
    return response()->json(['message' => 'API routes are working!']);
});
