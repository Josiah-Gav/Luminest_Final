<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\HouseTypeController;
use App\Http\Controllers\Api\PropertyManager\MaintenanceRequestController as ManagerMaintenanceController;
use App\Http\Controllers\Api\PropertyManager\ReservationController as ManagerReservationController;
use App\Http\Controllers\Api\PropertyManager\StaffController as ManagerStaffController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\Staff\MaintenanceRequestController as StaffMaintenanceController;
use App\Http\Controllers\Api\Tenant\HouseController as TenantHouseController;
use App\Http\Controllers\Api\Tenant\MaintenanceRequestController as TenantMaintenanceController;
use Illuminate\Support\Facades\Route;

// M1 Authentication & Profile (plan §5.1). The CSRF cookie comes from Sanctum's GET /sanctum/csrf-cookie.
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// M2 House Catalog (plan §5.2): public.
Route::get('/house-types', [HouseTypeController::class, 'index']);
Route::get('/house-types/{houseType:slug}', [HouseTypeController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // M3 Reservation & Purchase (plan §5.3). A tenant keeps seeing their purchase and receipts.
    Route::get('/reservations', [ReservationController::class, 'index'])->middleware('role:prospect,tenant');
    Route::middleware('role:prospect')->group(function () {
        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::post('/reservations/{reservation}/payments', [ReservationController::class, 'pay']);
        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
    });

    // Payer, property manager or admin: checked by ReservationPolicy::downloadReceipt.
    Route::get('/payments/{payment}/receipt', ReceiptController::class);

    // M4 Maintenance (plan §5.4–5.6). Tenant files and confirms, PM assigns, staff does the work.
    Route::middleware('role:tenant')->prefix('tenant')->group(function () {
        Route::get('/houses', [TenantHouseController::class, 'index']);
        Route::get('/maintenance-requests', [TenantMaintenanceController::class, 'index']);
        Route::post('/maintenance-requests', [TenantMaintenanceController::class, 'store']);
        Route::post('/maintenance-requests/{maintenanceRequest}/resolve', [TenantMaintenanceController::class, 'resolve']);
    });

    Route::middleware('role:maintenance_staff')->prefix('staff')->group(function () {
        Route::get('/maintenance-requests', [StaffMaintenanceController::class, 'index']);
        Route::patch('/maintenance-requests/{id}', [StaffMaintenanceController::class, 'update']);
    });

    Route::middleware('role:property_manager')->prefix('manager')->group(function () {
        // Reservations are read-only for the PM (R7).
        Route::get('/reservations', [ManagerReservationController::class, 'index']);
        Route::get('/maintenance-requests', [ManagerMaintenanceController::class, 'index']);
        Route::patch('/maintenance-requests/{maintenanceRequest}/assignment', [ManagerMaintenanceController::class, 'assign']);
        Route::get('/staff', [ManagerStaffController::class, 'index']);
    });
});
