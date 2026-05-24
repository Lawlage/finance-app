<?php

declare(strict_types=1);

use App\Http\Controllers\JobStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::get('/job-statuses', [JobStatusController::class, 'index']);
    Route::delete('/job-statuses/{jobStatus}', [JobStatusController::class, 'dismiss']);
});
