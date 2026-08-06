<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceApiController;

/*
|--------------------------------------------------------------------------
| API Routes - MACSON REST API for External Systems / UniFi Controller
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::get('/devices', [DeviceApiController::class, 'index']);
    Route::post('/devices', [DeviceApiController::class, 'store']);
    Route::get('/devices/{mac}', [DeviceApiController::class, 'show']);
    Route::put('/devices/{mac}', [DeviceApiController::class, 'update']);
    Route::delete('/devices/{mac}', [DeviceApiController::class, 'destroy']);
    Route::post('/devices/{mac}/verify', [DeviceApiController::class, 'verify']);
});
