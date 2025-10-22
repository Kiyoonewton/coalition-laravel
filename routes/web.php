<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Main page
Route::get('/', [ProductController::class, 'index']);

// API routes - ALL using web middleware (includes CSRF)
Route::prefix('api')->group(function () {
    Route::get('/products', [ProductController::class, 'getProducts']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});