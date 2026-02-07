<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/auth/register', [App\Http\Controllers\AuthController::class, 'register']);
Route::post('/auth/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/auth/forgot-password', [App\Http\Controllers\AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [App\Http\Controllers\AuthController::class, 'resetPassword']);

// Public catalog routes (no auth required)
Route::get('/products', [App\Http\Controllers\CatalogController::class, 'index']);
Route::get('/products/search', [App\Http\Controllers\CatalogController::class, 'search']);
Route::get('/products/{slug}', [App\Http\Controllers\CatalogController::class, 'show']);
Route::get('/categories', [App\Http\Controllers\CatalogController::class, 'categories']);
Route::get('/attributes', [App\Http\Controllers\CatalogController::class, 'attributes']);

// Protected routes – Sanctum ensures the user is authenticated
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth & Profile
    Route::get('/me', [App\Http\Controllers\AuthController::class, 'me']);
    Route::post('/auth/logout', [App\Http\Controllers\AuthController::class, 'logout']);
    Route::patch('/profile', [App\Http\Controllers\AuthController::class, 'updateProfile']);
    Route::post('/profile/change-password', [App\Http\Controllers\AuthController::class, 'changePassword']);

    // Cart & Checkout
    Route::get('/cart', [App\Http\Controllers\CartController::class, 'show']);
    Route::post('/cart/items', [App\Http\Controllers\CartController::class, 'addItem']);
    Route::patch('/cart/items/{id}', [App\Http\Controllers\CartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [App\Http\Controllers\CartController::class, 'removeItem']);
    Route::delete('/cart', [App\Http\Controllers\CartController::class, 'clear']);
    Route::post('/cart/merge', [App\Http\Controllers\CartController::class, 'merge']);

    Route::post('/checkout/quote-shipping', [App\Http\Controllers\CheckoutController::class, 'quoteShipping']);
    Route::post('/checkout/place-order', [App\Http\Controllers\CheckoutController::class, 'placeOrder']);

    // Customer Orders
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index']);
    Route::get('/orders/{id}', [App\Http\Controllers\OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [App\Http\Controllers\OrderController::class, 'cancel']);

    // Vendor/Admin Product Management
    Route::prefix('vendor')->group(function () {
        Route::get('/products', [App\Http\Controllers\ProductController::class, 'index']);
        Route::post('/products', [App\Http\Controllers\ProductController::class, 'store']);
        Route::get('/products/{id}', [App\Http\Controllers\ProductController::class, 'show']);
        Route::patch('/products/{id}', [App\Http\Controllers\ProductController::class, 'update']);
        Route::delete('/products/{id}', [App\Http\Controllers\ProductController::class, 'destroy']);

        // Vendor Orders
        Route::get('/orders', [App\Http\Controllers\OrderController::class, 'vendorOrders']);
        Route::patch('/orders/{id}/status', [App\Http\Controllers\OrderController::class, 'updateStatus']);
        Route::post('/orders/{id}/fulfill', [App\Http\Controllers\OrderController::class, 'fulfill']);
    });

    // Logistics – admin & vendor only
    Route::prefix('shipping')->group(function () {
        Route::get('/zones', [App\Http\Controllers\LogisticsController::class, 'zones']);
        Route::post('/zones', [App\Http\Controllers\LogisticsController::class, 'storeZone']);
        Route::post('/zones/{id}/rules', [App\Http\Controllers\LogisticsController::class, 'storeZoneRule']);
        Route::get('/methods', [App\Http\Controllers\LogisticsController::class, 'methods']);
    });
});