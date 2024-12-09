<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\rest\PropertyController;



Route::post('login', [AuthController::class, 'login']);

Route::post('create-tenant', [AuthController::class, 'create']);

// Protected routes with 'api' prefix and Sanctum middleware
Route::prefix('tenant')->middleware('auth:sanctum')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::post('create', [UserProfileController::class, '']);
        Route::get('profile', [AuthController::class, 'profile']);

        
    });


    Route::prefix('maintenance')->group(function () {
        // Route::post('create', [MaintenanceController::class, 'create']);

    });
});
Route::prefix('landlord')->middleware('auth:sanctum')->group(function () {
    Route::prefix('property')->group(function () {
        Route::post('create', [PropertyController::class, 'create']);


    });
    Route:
});
Route::prefix('handyman')->middleware('auth:sanctum')->group(function () {
     

});