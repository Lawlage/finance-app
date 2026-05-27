<?php

declare(strict_types=1);

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::patch('/transactions/{transaction}/category', [TransactionController::class, 'updateCategory'])->name('transactions.update-category');

    Route::get('/upload', [UploadController::class, 'index'])->name('upload');
    Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::post('/categories', [CategoryController::class, 'storeCategory'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroyCategory'])->name('categories.destroy');
    Route::post('/category-rules', [CategoryController::class, 'storeRule'])->name('category-rules.store');
    Route::patch('/category-rules/{categoryRule}', [CategoryController::class, 'updateRule'])->name('category-rules.update');
    Route::delete('/category-rules/{categoryRule}', [CategoryController::class, 'destroyRule'])->name('category-rules.destroy');
    Route::post('/categories/recategorize', [CategoryController::class, 'recategorize'])->name('categories.recategorize');

    Route::delete('/imports/{import}', [ImportController::class, 'destroy'])->name('imports.destroy');

    Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis');
    Route::get('/analysis/{analysisRun}', [AnalysisController::class, 'show'])->name('analysis.show');
    Route::delete('/analysis/{analysisRun}', [AnalysisController::class, 'destroy'])->name('analysis.destroy');

    Route::get('/privacy', [PrivacyController::class, 'index'])->name('privacy');
    Route::patch('/privacy/settings', [PrivacyController::class, 'updateSettings'])->name('privacy.settings');
    Route::post('/replacement-rules', [PrivacyController::class, 'storeRule'])->name('replacement-rules.store');
    Route::patch('/replacement-rules/{replacementRule}', [PrivacyController::class, 'updateRule'])->name('replacement-rules.update');
    Route::delete('/replacement-rules/{replacementRule}', [PrivacyController::class, 'destroyRule'])->name('replacement-rules.destroy');
});
