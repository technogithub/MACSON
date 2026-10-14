<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SsidController;
use App\Http\Controllers\LogController;

use App\Http\Controllers\VoucherController;
use App\Http\Controllers\DhcpController;

use App\Http\Middleware\EnsureSuperAdmin;

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

    // UniFi Hotspot Voucher Routes (Accessible to both Super Admin & Operator)
    Route::get('vouchers/print', [VoucherController::class, 'print'])->name('vouchers.print');
    Route::post('vouchers/sync', [VoucherController::class, 'syncNow'])->name('vouchers.sync');
    Route::post('vouchers/batch-revoke', [VoucherController::class, 'batchRevoke'])->name('vouchers.batchRevoke');
    Route::resource('vouchers', VoucherController::class)->only(['index', 'store', 'destroy']);

    // ── Super Admin Only Restricted Routes ─────────────────────────────
    Route::middleware(EnsureSuperAdmin::class)->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Device MAC Address Management Routes
        Route::get('devices/export', [DeviceController::class, 'exportCsv'])->name('devices.export');
        Route::get('devices/template-csv', [DeviceController::class, 'templateCsv'])->name('devices.template');
        Route::post('devices/import', [DeviceController::class, 'importCsv'])->name('devices.import');
        Route::patch('devices/{device}/toggle', [DeviceController::class, 'toggleStatus'])->name('devices.toggle');
        // Suggest free IP in subnet
        Route::get('/devices/suggest-ip', [DeviceController::class, 'suggestIp'])->name('devices.suggest-ip');
        Route::resource('devices', DeviceController::class);

        // Multi-SSID Management Routes
        Route::resource('ssids', SsidController::class)->except(['create', 'edit', 'show']);

        // UniFi Controller Config
        Route::post('vouchers/config', [VoucherController::class, 'updateConfig'])->name('vouchers.config');

        // DHCP Server Management Routes
        Route::get('/dhcp', [DhcpController::class, 'index'])->name('dhcp.index');
        Route::post('/dhcp/subnets', [DhcpController::class, 'storeSubnet'])->name('dhcp.subnets.store');
        Route::put('/dhcp/subnets/{id}', [DhcpController::class, 'updateSubnet'])->name('dhcp.subnets.update');
        Route::delete('/dhcp/subnets/{id}', [DhcpController::class, 'destroySubnet'])->name('dhcp.subnets.destroy');

        Route::get('/dhcp/static/template-csv', [DhcpController::class, 'templateCsv'])->name('dhcp.static.template');
        Route::post('/dhcp/static/import-csv', [DhcpController::class, 'importCsv'])->name('dhcp.static.import');
        Route::post('/dhcp/static-leases', [DhcpController::class, 'storeStaticLease'])->name('dhcp.static.store');
        Route::put('/dhcp/static-leases/{id}', [DhcpController::class, 'updateStaticLease'])->name('dhcp.static.update');
        Route::delete('/dhcp/static-leases/{id}', [DhcpController::class, 'destroyStaticLease'])->name('dhcp.static.destroy');
        Route::post('/dhcp/sync', [DhcpController::class, 'syncConfig'])->name('dhcp.sync');

        // RADIUS & Audit Access Log Routes
        Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
        Route::delete('/logs/clear', [LogController::class, 'clear'])->name('logs.clear');
    });
});
