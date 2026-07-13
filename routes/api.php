<?php

use App\Http\Controllers\CallStatController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SyncJobController;
use App\Http\Controllers\SyncLogController;
use App\Http\Controllers\SystemAlertController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/user', [LoginController::class, 'user']);

    Route::get('/call-stats', [CallStatController::class, 'index']);
    Route::get('/call-stats/today', [CallStatController::class, 'today']);
    Route::get('/call-stats/campaigns', [CallStatController::class, 'campaigns']);
    Route::get('/call-stats/campaigns/range', [CallStatController::class, 'campaignsRange']);
    Route::post('/call-stats/refresh', [CallStatController::class, 'refresh']);

    Route::get('/sync-jobs/{syncJob}', [SyncJobController::class, 'show']);
    Route::get('/sync-logs', [SyncLogController::class, 'index']);

    Route::get('/system-alerts', [SystemAlertController::class, 'index']);
    Route::post('/system-alerts/{alert}/dismiss', [SystemAlertController::class, 'dismiss']);
});
