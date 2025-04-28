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
use App\Http\Controllers\rest\ReportsController;
use App\Http\Controllers\rest\HandymanController;
use App\Http\Controllers\rest\PropertyController;
use App\Http\Controllers\rest\PickedRoomController;
use App\Http\Controllers\rest\ReservationController;
use App\Http\Controllers\rest\UserProfileController;
use App\Http\Controllers\rest\NotificationController;
use App\Http\Controllers\rest\RentalAgreementController;
use App\Http\Controllers\rest\MaintenanceRequestController;
use App\Http\Controllers\rest\SettingController;
use App\Http\Controllers\rest\DashboardController;


Route::post('/register-webhook', [PaymentController::class, 'registerWebhook']);

Route::post('/webhook-handler', [PaymentController::class, 'handleWebhook']);
Route::get('/payment-success', [PaymentController::class, 'paymentSuccess']);

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
        Route::get('indexByProfileId/{profileId}', [ReservationController::class, 'IndexByProfileId']);
    });

    Route::prefix('billing')->group(function() {
        Route::get('index',[BillingController::class, 'index']);
        Route::get('getbillingforrentalagreement/{rentalagreement_code}', [BillingController::class, 'getBillingForRentalAgreement']);
        Route::get('get-billing-details/{billingId}', [BillingController::class, 'getBillingDetails']);
        Route::get('retrieve-latest-billing-for-monthly-rent/{user_id}', [BillingController::class, 'retrieveLatestBillingForMonthlyRent']);
    });

    Route::prefix('payment')->group(function() {
        Route::post('store-payment-after-paymongo', [PaymentController::class, 'storePaymentAfterPayMongo']);
        Route::post('process-payment', [PaymentController::class, 'processPayment']);
        Route::get('retrieve-payment/{billingId}', [PaymentController::class,'retrievePayment']);
        Route::get('retrieve-receipt/{profileId}', [PaymentController::class, 'getAllReceiptUrlsByProfileId']);
        Route::get('check-fail-payment/{user_id}', [PaymentController::class, 'checkFailPayment']);
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
        Route::get('show/{id}/pdf', [RentalAgreementController::class, 'downloadPdf']);
        Route::post('store/{reservation_id}', [RentalAgreementController::class, 'store']);
        Route::get('view-pdf/{agreementId}', [RentalAgreementController::class, 'generatePdfUrl']);
        Route::get('index-profileId/{profileId}', [RentalAgreementController::class, 'indexByProfileId']);
        Route::get('show-active-Rental-agreement/{profileId}', [RentalAgreementController::class, 'ShowActiveRentalAgreementByProfileId']);
        Route::get('view-contract-countdown/{agreementId}', [RentalAgreementController::class, 'ViewContractCountdown']);
    });

    Route::prefix('property')->group(function () {
        Route::get('index', [PropertyController::class, 'index']);

    });

    Route::prefix('room')->group(function () {
        Route::get('property/{property_id}', [RoomController::class, 'showRoomsByPropertyId']);
    });

    Route::prefix('maintenance_request')->group(function () {
        Route::post('create-maintenance-request', [MaintenanceRequestController::class, 'createMaintenanceRequest']);
        Route::get('index-by-tenant-id/{tenant_id}', [MaintenanceRequestController::class, 'indexByTenantId']);
        Route::get('show/{maintenance_request_id}', [MaintenanceRequestController::class, 'show']);
        Route::post('update/{maintenance_request_id}', [MaintenanceRequestController::class, 'update']);
        Route::patch('cancel/{maintenance_request_id}', [MaintenanceRequestController::class, 'updateStatusToCancel']);

    });


    Route::prefix('tenant')->group(function() {
        Route::get('index', [TenantController::class, 'index']);
        Route::post('store', [TenantController::class, 'store']);
        Route::get('show/{tenant_id}', [TenantController::class, 'show']);
        Route::get('showbyprofileId/{profile_id}', [TenantController::class, 'showByProfileId']);
        Route::delete('destroy/{tenant_id}', [TenantController::class, 'destroy']);
        Route::delete('destroybyprofile_id/{profile_id}', [TenantController::class, 'destroyByProfileId']);
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
        Route::get('room-tenant/{room_id}',[TenantController::class, 'getTenantViaRoomId']);
        Route::delete('destroy/{id}', [RoomController::class, 'destroy']);
    });

    Route::prefix('reservation')->group(function() {
        Route::get('index',[ReservationController::class, 'index']);
        Route::post('updateStatus/{id}', [ReservationController::class, 'updateStatus']);
        Route::get('show/{id}',[ReservationController::class,'show']);
    });

    Route::prefix('rental_agreement')->group(function(){
        Route::get('index', [RentalAgreementController::class, 'index']);
        Route::post('store', [RentalAgreementController::class, 'store']);
        Route::get('show/{rentalagreement_id}', [RentalAgreementController::class, 'show']);
        Route::post('update/{rentalagreement_id}', [RentalAgreementController::class, 'update']);
        Route::get('get-rooms-by-profileid/{profileId}', [RentalAgreementController::class, 'getRoomsByProfileId']);
        Route::get('view-pdf/{agreementId}', [RentalAgreementController::class, 'generatePdfUrl']);

    });

    Route::prefix('handy_man')->group(function () {
        Route::post('create-handyman', [AuthController::class, 'createHandyman']);
        Route::post('post-update-handyman', [AuthController::class, 'updateHandyman']);
        Route::get('index', [HandymanController::class, 'index']);
        Route::get('show/{handymanId}', [HasndymanController::class, 'show']);
        Route::post('terminate-handyman/{handymanId}', [HandymanController::class, 'terminateHandyman']);
        Route::get('get-available-handyman-list', [HandymanController::class, 'getAvailableHandymanList']);
        Route::get('get-terminated-handyman-list', [HandymanController::class, 'getTerminatedHandymanList']);
        Route::get('get-busy-handyman-list', [HandymanController::class, 'getBusyHandymanList']);
        Route::get('admin-handyman-index', [HandymanController::class, 'adminHandymanIndex']);
    });

    Route::prefix('maintenance_request')->group(function () {
        Route::get('index', [MaintenanceRequestController::class, 'index']);
        Route::get('show/{maintenance_id)', [MaintenanceRequestController::class, 'show']);
        Route::get('update/{maintenance_id)', [MaintenanceRequestController::class, 'update']);
        Route::post('patch-maintenance-request-to-requested/{maintenance_request_id}/{handyman_id}', [MaintenanceRequestController::class, 'patchMaintenanceRequestToRequested']);
        Route::get('get-requested-maintenance-requests', [MaintenanceRequestController::class, 'getMaintenanceRequestListRequested']);
        Route::post('/assign-maintenance-request', [MaintenanceRequestController::class, 'patchMaintenanceRequestToAssigned']);
        Route::post('/approve-maintenance-request', [MaintenanceRequestController::class, 'patchMaintenanceRequestToComplete']);
    }); 

    Route::prefix('billing')->group(function () {
        Route::get('index', [BillingController::class, 'index']);
    }); 

    Route::prefix('payment')->group(function () {
        Route::get('admin-index', [PaymentController::class, 'adminIndex']);
    });
    
    Route::prefix('reports')->group(function () {
        Route::get('reports',[ReportsController::class, 'index']);
    });
    
    Route::prefix('user')->group(function () {
        // Route::get('index', [UserController::class, 'index']);
        Route::get('users-lists-with-relations', [UserController::class, 'getUsersAndItsRelations']);
        Route::get('admin-show-tenant-by-profile-id/{profile_id}', [TenantController::class, 'adminShowTenantByProfileId']);
        Route::post('send-overdue-warning-to-tenant',[NotificationController::class, 'sendOverdueWarningToTenant']);
        Route::put('update-user-data/{user_id}', [TenantController::class, 'updateUserData']);
        Route::put('update-tenant-profile/{tenant_id}', [TenantController::class, 'updateTenantProfile']);
    });

    Route::prefix('setting')->group(function () {
        Route::post('update-or-create-setting',[SettingController::class, 'updateOrCreateSetting']);
        Route::get('show-setting/{user_id}',[SettingController::class, 'show']);
        Route::put('update-admin/{user_id}',[SettingController::class, 'updateAdmin']);
        
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('index',[DashboardController::class, 'index']);
    });
});

Route::prefix('handyman')->middleware('auth:sanctum')->group(function () {
     
    Route::prefix('handy_man')->group(function () {
        Route::get('index', [HandymanController::class, 'index']);
        Route::get('show-handyman-by-user-id/{user_id}', [HandymanController::class, 'showHandymanByUserId']);
    });

    Route::prefix('maintenance_request')->group(function () {
        Route::get('get-maintenance-request-by-handymanId/{handymanId}', [MaintenanceRequestController::class, 'getMaintenanceRequestByHandymanId']);
        Route::get('get-maintenance-request', [MaintenanceRequestController::class, 'getMaintenanceRequestList']);
        Route::get('get-pending-maintenance-request', [MaintenanceRequestController::class, 'getPendingMaintenanceRequestList']);
        Route::patch('patch-maintenance-request-to-requested/{maintenance_request_id}/{handyman_id}', [MaintenanceRequestController::class, 'patchMaintenanceRequestToRequested']);
        Route::get('get-requested-maintenance-request/{handyman_id}', [MaintenanceRequestController::class, 'getMaintenanceRequestListRequestedByHandymanId']);
        Route::patch('patch-maintenance-request-to-in-progress/{maintenance_request_id}', [MaintenanceRequestController::class, 'patchMaintenanceRequestToInProgress']);
        Route::patch('patch-maintenance-request-to-for-approve/{maintenance_request_id}', [MaintenanceRequestController::class, 'patchMaintenanceRequestToForApprove']);
        // Route::patch('')

    });
});