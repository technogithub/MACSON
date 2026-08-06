<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SsidController;
use App\Http\Controllers\LogController;

/*
|--------------------------------------------------------------------------
| Web Routes - MACSON Dashboard & Management UI
|--------------------------------------------------------------------------
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Device MAC Address Management Routes
Route::get('devices/export', [DeviceController::class, 'exportCsv'])->name('devices.export');
Route::post('devices/import', [DeviceController::class, 'importCsv'])->name('devices.import');
Route::patch('devices/{device}/toggle', [DeviceController::class, 'toggleStatus'])->name('devices.toggle');
Route::resource('devices', DeviceController::class);

// Multi-SSID Management Routes
Route::resource('ssids', SsidController::class)->except(['create', 'edit', 'show']);

// RADIUS & Audit Access Log Routes
Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
Route::delete('/logs/clear', [LogController::class, 'clear'])->name('logs.clear');
