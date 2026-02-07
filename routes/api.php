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

// Protected routes – Sanctum ensures the user is authenticated
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [App\Http\Controllers\AuthController::class, 'me']);
    Route::post('/auth/logout', [App\Http\Controllers\AuthController::class, 'logout']);

    // Catalog
    Route::get('/products', [App\Http\Controllers\CatalogController::class, 'index']);
    Route::get('/products/{slug}', [App\Http\Controllers\CatalogController::class, 'show']);
    Route::get('/categories', [App\Http\Controllers\CatalogController::class, 'categories']);
    Route::get('/attributes', [App\Http\Controllers\CatalogController::class, 'attributes']);

    // Cart & Checkout
    Route::get('/cart', [App\Http\Controllers\CartController::class, 'show']);
    Route::post('/cart/items', [App\Http\Controllers\CartController::class, 'addItem']);
    Route::patch('/cart/items/{id}', [App\Http\Controllers\CartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [App\Http\Controllers\CartController::class, 'removeItem']);
    Route::post('/checkout/quote-shipping', [App\Http\Controllers\CheckoutController::class, 'quoteShipping']);
    Route::post('/checkout/place-order', [App\Http\Controllers\CheckoutController::class, 'placeOrder']);

    // Orders
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index']);
    Route::get('/orders/{id}', [App\Http\Controllers\OrderController::class, 'show']);
    Route::patch('/orders/{id}/status', [App\Http\Controllers\OrderController::class, 'updateStatus']);
    Route::post('/orders/{id}/fulfill', [App\Http\Controllers\OrderController::class, 'fulfill']);

    // Logistics – admin & vendor only (policy checks inside controllers)
    Route::get('/shipping/zones', [App\Http\Controllers\LogisticsController::class, 'zones']);
    Route::post('/shipping/zones', [App\Http\Controllers\LogisticsController::class, 'storeZone']);
    Route::post('/shipping/zones/{id}/rules', [App\Http\Controllers\LogisticsController::class, 'storeZoneRule']);
    Route::get('/shipping/methods', [App\Http\Controllers\LogisticsController::class, 'methods']);
});