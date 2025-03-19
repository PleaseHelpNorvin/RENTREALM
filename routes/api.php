<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\rest\RoomController;
use App\Http\Controllers\rest\UserController;
use App\Http\Controllers\rest\TenantController;
use App\Http\Controllers\rest\AddressController;
use App\Http\Controllers\rest\BillingController;
use App\Http\Controllers\rest\InquiryController;
use App\Http\Controllers\rest\PaymentController;
use App\Http\Controllers\rest\PropertyController;
use App\Http\Controllers\rest\PickedRoomController;
use App\Http\Controllers\rest\ReservationController;
use App\Http\Controllers\rest\UserProfileController;
use App\Http\Controllers\rest\NotificationController;
use App\Http\Controllers\rest\RentalAgreementController;


Route::post('/register-webhook', [PaymentController::class, 'registerWebhook']);

Route::post('/webhook-handler', [PaymentController::class, 'handleWebhook']);

Route::post('login', [AuthController::class, 'login']);
Route::post('create/tenant', [AuthController::class, 'create']);
Route::get('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::prefix('property')->group(function () {
    Route::get('index', [PropertyController::class, 'index']);
    Route::get('show/{id}',[PropertyController::class, 'show']);
    Route::get('search', [PropertyController::class, 'search']);
});

Route::prefix('room')->group(function () {
    Route::get('property/{property_id}', [RoomController::class, 'showRoomsByPropertyId']);
    Route::get('show/{id}',[RoomController::class, 'show']);
    
});
Route::prefix('inquiry')->group(function(){
    Route::post('store', [InquiryController::class, 'store']);
    Route::get('index', [InquiryController::class, 'index']);
    Route::get('show/{inquiry_id}', [InquiryController::class, 'show']);
});


// Route::post('/webhook/paymongo', function (Request $request) {
//     $payload = $request->json();

//     if ($payload['data']['attributes']['status'] === 'paid') {
//         $paymentId = $payload['data']['id'];

//         Payment::where('payment_reference', $paymentId)
//             ->update(['status' => 'paid']);
//     }

//     return response()->json(['message' => 'Webhook received'], 200);
// });
Route::get('show/{id}/pdf', [RentalAgreementController::class, 'downloadPdf']);


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

    Route::prefix('picked_room')->group(function () {
        Route::get('index', [PickedRoomController::class, 'index']);
        Route::get('getRoomsByUser/{userId}', [PickedRoomController::class, 'getRoomsByUser']);
        Route::post('addRoomForUser', [PickedRoomController::class, 'addRoomForUser']);
        Route::delete('destroy/{pickedroom_id}', [PickedRoomController::class, 'destroy']);
    });

    Route::prefix('reservation')->group(function() {
        Route::get('index',[ReservationController::class, 'index']);
        Route::post('store',[ReservationController::class, 'store']);
        Route::get('show/{id}',[ReservationController::class,'show']);

    });

    Route::prefix('billing')->group(function() {
        Route::get('index',[BillingController::class, 'index']);
        Route::get('getbillingforrentalagreement/{rentalagreement_code}', [BillingController::class, 'getBillingForRentalAgreement']);
        Route::get('get-billing-details/{billingId}', [BillingController::class, 'getBillingDetails']);
        // Route::post('store-payment-after-paymongo', []);
    });

    Route::prefix('payment')->group(function() {
        Route::post('store-payment-after-paymongo', [PaymentController::class, 'storePaymentAfterPayMongo']);
        Route::post('process-payment', [PaymentController::class, 'processPayment']);
        Route::get('retrieve-payment/{billingId}', [PaymentController::class,'retrievePayment']);

    });

    Route::prefix('notification')->group(function() {
        Route::get('index/{user_id}', [NotificationController::class,'index']);
        Route::patch('updateIsRead/{notif_id}',[NotificationController::class, 'updateIsRead']);
        Route::get('show/{id}',[ReservationController::class,'show']);

    });

    Route::prefix('rental_agreement')->group(function(){
        Route::get('index', [RentalAgreementController::class, 'index']);
        Route::post('store', [RentalAgreementController::class, 'store']);
        Route::get('show/{agreementCode}', [RentalAgreementController::class, 'show']);
        // Route::get('show/{id}/pdf', [RentalAgreementController::class, 'downloadPdf']);
        Route::post('store/{reservation_id}', [RentalAgreementController::class, 'store']);

    });

    Route::prefix('property')->group(function () {
        Route::get('index', [PropertyController::class, 'index']);

    });

    Route::prefix('room')->group(function () {
        Route::get('property/{property_id}', [RoomController::class, 'showRoomsByPropertyId']);
    });

    // Route::prefix('rental_agreement')->group(function () {
    //     Route::get('index', [RentalAgreementController::class,'index']);
        // Route::get('show/{reservation_code}')
    // });


    Route::prefix('tenant')->group(function() {
        Route::get('index', [TenantController::class, 'index']);
        Route::post('store', [TenantController::class, 'store']);
        Route::get('show/{tenant_id}', [TenantController::class, 'show']);
        Route::get('showbyprofile_id/{profile_id}', [TenantController::class, 'showByProfileId']);
        Route::delete('destroy/{tenant_id}', [TenantController::class, 'destroy']);
        Route::delete('destroybyprofile_id/{profile_id}', [TenantController::class, 'destroyByProfileId']);
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
    Route::prefix('reservation')->group(function() {
        Route::get('index',[ReservationController::class, 'index']);
        Route::patch('updateStatus/{id}',[ReservationController::class, 'updateStatus']);
        Route::get('show/{id}',[ReservationController::class,'show']);
    });

    Route::prefix('rental_agreement')->group(function(){
        Route::get('index', [RentalAgreementController::class, 'index']);
        Route::post('store', [RentalAgreementController::class, 'store']);
        Route::get('show/{rentalagreement_id}', [RentalAgreementController::class, 'show']);
        Route::post('update/{rentalagreement_id}', [RentalAgreementController::class, 'update']);
    });
    // Route::prefix('inquiry')->group(function () {
    //     Route::post('store', [InquiryController::class, 'store']);
    //     Route::get('index', [InquiryController::class, 'index']);
    //     Route::get('show/{inquiry_id}', [InquiryController::class, 'show']);
    //     Route::patch('update/{inquiry_id}', [InquiryController::class, 'update']);
    // });
});

Route::prefix('handyman')->middleware('auth:sanctum')->group(function () {
     

});