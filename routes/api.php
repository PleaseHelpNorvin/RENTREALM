<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\rest\RoomController;
use App\Http\Controllers\rest\UserController;
use App\Http\Controllers\rest\PropertyController;
use App\Http\Controllers\rest\UserProfileController;
use App\Http\Controllers\rest\RentalAgreementController;



Route::post('login', [AuthController::class, 'login']);
Route::post('create/tenant', [AuthController::class, 'create']);
Route::get('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
// Protected routes with 'api' prefix and Sanctum middleware
Route::prefix('tenant')->middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function() {
        Route::get('index', [UserController::class, 'index']);
        Route::get('show/{id}', [UserController::class, 'show']);
        Route::post('update/{id}', [UserController::class, 'update']);
    });
    
    Route::prefix('profile')->group(function () {
        Route::get('index', [UserProfileController::class,'index']);
        Route::post('store/{user_id}', [UserProfileController::class, 'store']);
        Route::post('storepicture/{user_id}', [UserProfileController::class, 'storePicture']);
        Route::get('show/{user_id}', [UserProfileController::class, 'showByUserId']);
        Route::post('update/{user_id}', [UserProfileController::class, 'update']);
    });

    Route::prefix('rental_agreement')->group(function(){
        Route::get('index', [RentalAgreementController::class, 'index']);
        Route::post('store', [RentalAgreementController::class, 'store']);
        Route::get('show/{rentalagreement_id}', [RentalAgreementController::class, 'show']);
    });


    Route::prefix('maintenance')->group(function () {

    });
});

Route::prefix('landlord')->middleware('auth:sanctum')->group(function () {
    //property working crud
    Route::prefix('property')->group(function () {
        Route::get('index', [PropertyController::class, 'index']);
        Route::post('store', [PropertyController::class, 'store']);
        Route::get('show/{id}',[PropertyController::class, 'show']);
        Route::post('update/{id}', [PropertyController::class, 'update']);
        Route::delete('destroy/{id}', [PropertyController::class, 'destroy']);
    });
    
    Route::prefix('room')->group(function() {
        Route::get('index', [RoomController::class, 'index']);
        Route::post('store', [RoomController::class, 'store']);
        Route::get('property/{property_id}', [RoomController::class, 'showRoomsByPropertyId']);
        Route::get('show/{id}',[RoomController::class, 'show']);
        Route::post('update/{id}', [RoomController::class, 'update']);
        Route::delete('destroy/{id}', [RoomController::class, 'destroy']);
    });


    Route::prefix('rental_agreement')->group(function(){
        Route::get('index', [RentalAgreementController::class, 'index']);
        Route::post('store', [RentalAgareementController::class, 'store']);
        Route::get('show/{rentalagreement_id}', [RentalAgreementController::class, 'show']);
        Route::post('update/{rentalagreement_id}', [RentalAgreementController::class, 'update']);
    });
    // Route::prefix('profile')->group(function () {
    //     Route::post('show/{id}', [UserProfileController::class, 'show']);
    // });
});

Route::prefix('handyman')->middleware('auth:sanctum')->group(function () {
     

});