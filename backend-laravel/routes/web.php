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
Route::resource('devices', DeviceController::class);
Route::post('devices/{device}/toggle', [DeviceController::class, 'toggleStatus'])->name('devices.toggle');

// Multi-SSID Management Routes
Route::resource('ssids', SsidController::class)->except(['create', 'edit', 'show']);

// RADIUS & Audit Access Log Routes
Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
Route::delete('/logs/clear', [LogController::class, 'clear'])->name('logs.clear');
