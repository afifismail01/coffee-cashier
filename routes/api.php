<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionDetailController;
use App\Http\Controllers\ReportController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // CRUD Product
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    // CRUD Transaction
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transaction/{id}', [TransactionController::class, 'show']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::delete('/transaction/{id}', [TransactionController::class, 'destroy']);
    // CRUD TransactionDetail
    Route::get('/transaction/{id}/details', [TransactionDetailController::class, 'index']);
    Route::post('/transaction/{id}/details', [TransactionDetailController::class, 'store']);
    Route::put('/transaction-detail/{id}', [TransactionDetailController::class, 'update']);
    Route::delete('/transaction-detail/{id}', [TransactionDetailController::class, 'destroy']);
    // Report routes
    Route::prefix('reports')->group(function () {
        Route::get('/daily', [ReportController::class, 'dailyReport']);
        Route::get('/monthly', [ReportController::class, 'monthlyReport']);
    });
});
