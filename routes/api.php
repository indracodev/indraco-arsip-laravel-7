<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('health/ping', [HealthController::class, 'ping'])->name('health.ping');
Route::get('health/ready', [HealthController::class, 'ready'])->name('health.ready');
