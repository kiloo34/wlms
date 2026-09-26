<?php

declare(strict_types=1);

use App\Modules\Notification\Presentation\Http\Controllers\GetNotificationsController;
use App\Modules\Notification\Presentation\Http\Controllers\MarkNotificationsReadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('notifications')->group(function () {
    Route::get('/', GetNotificationsController::class);
    Route::put('/read', MarkNotificationsReadController::class);
});

