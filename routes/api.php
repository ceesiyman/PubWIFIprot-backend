<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DnsController;
use App\Http\Controllers\Api\NetworkController;
use App\Http\Controllers\Api\NetworkReportController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\VpnController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes in this file are automatically prefixed with /api
|
*/

// Public routes
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// WiFi Networks - Public endpoints
Route::prefix('networks')->group(function () {
    Route::post('analyze', [NetworkController::class, 'analyze']);
    Route::post('check', [NetworkController::class, 'check']);
    Route::post('report', [NetworkReportController::class, 'store']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/user', [AuthController::class, 'user']);

    // Devices
    Route::apiResource('devices', DeviceController::class);

    // DNS & Domain Reputation
    Route::prefix('dns')->group(function () {
        Route::post('check', [DnsController::class, 'checkDomain']);
        Route::get('history', [DnsController::class, 'history']);
    });

    // Sessions
    Route::prefix('sessions')->group(function () {
        Route::post('start', [SessionController::class, 'start']);
        Route::post('end', [SessionController::class, 'end']);
        Route::post('update', [SessionController::class, 'update']);
        Route::get('history', [SessionController::class, 'history']);
    });

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::get('domains/malicious', [DnsController::class, 'maliciousDomains']);
        Route::post('domains/malicious', [DnsController::class, 'addMaliciousDomain']);
        Route::delete('domains/malicious/{domain}', [DnsController::class, 'removeMaliciousDomain']);
    });

    // VPN routes
    Route::prefix('vpn')->group(function () {
        Route::post('connect', [VpnController::class, 'connect']);
        Route::post('disconnect', [VpnController::class, 'disconnect']);
        Route::post('stats', [VpnController::class, 'updateStats']);
        Route::get('status', [VpnController::class, 'status']);
    });
}); 