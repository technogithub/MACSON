<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SsidController;
use App\Http\Controllers\LogController;

use App\Http\Controllers\VoucherController;

/*
|--------------------------------------------------------------------------
| Web Routes - MACSON Dashboard & Management UI
|--------------------------------------------------------------------------
*/

// ── Public Auth Routes ─────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// ── Protected Routes (must be authenticated) ───────────────────────────
Route::middleware('auth')->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Device MAC Address Management Routes
    Route::get('devices/export', [DeviceController::class, 'exportCsv'])->name('devices.export');
    Route::post('devices/import', [DeviceController::class, 'importCsv'])->name('devices.import');
    Route::patch('devices/{device}/toggle', [DeviceController::class, 'toggleStatus'])->name('devices.toggle');
    Route::resource('devices', DeviceController::class);

    // Multi-SSID Management Routes
    Route::resource('ssids', SsidController::class)->except(['create', 'edit', 'show']);

    // UniFi Hotspot Voucher Routes
    Route::get('vouchers/print', [VoucherController::class, 'print'])->name('vouchers.print');
    Route::post('vouchers/sync', [VoucherController::class, 'syncNow'])->name('vouchers.sync');
    Route::post('vouchers/config', [VoucherController::class, 'updateConfig'])->name('vouchers.config');
    Route::resource('vouchers', VoucherController::class)->only(['index', 'store', 'destroy']);

    // RADIUS & Audit Access Log Routes
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::delete('/logs/clear', [LogController::class, 'clear'])->name('logs.clear');
});
