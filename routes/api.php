<?php

declare(strict_types=1);

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Notifications
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])
        ->where('id', '[0-9]+');
    Route::get('/users/{userId}/notifications', [NotificationController::class, 'index'])
        ->where('userId', '[0-9]+');

    // Reports (bonus)
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports/{id}', [ReportController::class, 'show'])
        ->where('id', '[0-9]+');
    Route::get('/reports/{id}/download', [ReportController::class, 'download'])
        ->where('id', '[0-9]+');
});
