<?php

use App\Http\Controllers\Api\CekKehadiranController;
use App\Http\Controllers\Api\IzinController;
use App\Http\Controllers\Api\ScanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Scan API (authenticated — mirrors the CI4 app's `scan` route group,
| gated by the global session filter there)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('scan')->group(function () {
    Route::get('/bootstrap', [ScanController::class, 'bootstrap']);
    Route::post('/', [ScanController::class, 'scan']);
});

/*
|--------------------------------------------------------------------------
| Public portals (mirrors the CI4 app's `izin` and `cek-kehadiran` route
| groups, which were the only fully-public, session-exempt routes there).
| Rate-limited since neither NIS+phone nor NIS/NUPTK lookups are strong
| authentication — the migration plan flags this as a gap to fix (§4).
|--------------------------------------------------------------------------
*/
Route::middleware(['throttle:20,1'])->group(function () {
    Route::prefix('izin')->group(function () {
        Route::post('/lookup', [IzinController::class, 'lookup']);
        Route::post('/submit', [IzinController::class, 'submit']);
    });

    Route::post('/cek-kehadiran', [CekKehadiranController::class, 'view']);
});
