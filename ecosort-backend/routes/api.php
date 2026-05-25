<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WasteBankController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AiDetectionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\UserWalletController;
use App\Http\Controllers\PriceCatalogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WasteCategoryController;

// Auth Routes (Public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/auth/google', [AuthController::class, 'googleAuth']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth Routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);

    // Transaction & History Routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::patch('/transactions/{id}/status', [TransactionController::class, 'updateStatus']);

    // Manager Feature Routes
    Route::get('/manager/dashboard', [TransactionController::class, 'managerDashboard']);
    Route::get('/manager/inventory', [WasteBankController::class, 'managerInventory']);
    Route::put('/manager/waste-bank', [WasteBankController::class, 'update']);

    // Withdrawal Routes (Penarikan Saldo)
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);
    Route::patch('/withdrawals/{id}/status', [WithdrawalController::class, 'updateStatus']);

    // User Wallet / Bank Account Routes
    Route::get('/user/wallets', [UserWalletController::class, 'index']);
    Route::post('/user/wallets', [UserWalletController::class, 'store']);
    Route::put('/user/wallets/{id}', [UserWalletController::class, 'update']);
    Route::delete('/user/wallets/{id}', [UserWalletController::class, 'destroy']);

    // Admin Dashboard & User Management Routes
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'listUsers']);
    Route::post('/users', [AdminController::class, 'storeUser']);
    Route::put('/users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser']);
    Route::patch('/users/{id}/status', [AdminController::class, 'toggleStatus']);

    // Waste Bank / Location Management (Admin)
    Route::post('/waste-banks', [WasteBankController::class, 'adminStore']);
    Route::put('/waste-banks/{id}', [WasteBankController::class, 'adminUpdate']);
    Route::delete('/waste-banks/{id}', [WasteBankController::class, 'adminDestroy']);

    // Waste Category Management
    Route::get('/waste-categories', [WasteCategoryController::class, 'index']);

    // Price Catalog Management (Manager/Admin)
    Route::post('/price-catalogs', [PriceCatalogController::class, 'store']);
    Route::patch('/price-catalogs/{id}', [PriceCatalogController::class, 'update']);
    Route::delete('/price-catalogs/{id}', [PriceCatalogController::class, 'destroy']);
});

// Waste Bank Public Route (e.g. for listing nearest waste banks on maps)
Route::get('/waste-banks', [WasteBankController::class, 'index']);

// AI Detection (Public or Auth, based on requirements - currently Public)
Route::post('/ai/detect', [AiDetectionController::class, 'detect']);
