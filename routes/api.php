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

    // Reviews
    Route::get('/products/{productId}/reviews', [App\Http\Controllers\ReviewController::class, 'index']);
    Route::post('/products/{productId}/reviews', [App\Http\Controllers\ReviewController::class, 'store']);
    Route::patch('/reviews/{reviewId}', [App\Http\Controllers\ReviewController::class, 'update']);
    Route::delete('/reviews/{reviewId}', [App\Http\Controllers\ReviewController::class, 'destroy']);
    Route::post('/reviews/{reviewId}/vote', [App\Http\Controllers\ReviewController::class, 'vote']);

    // Admin routes - Protected by admin middleware
    Route::prefix('admin')->middleware('admin')->group(function () {
        // Dashboard & Analytics
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index']);
        Route::get('/sales-data', [App\Http\Controllers\Admin\DashboardController::class, 'salesData']);
        Route::get('/top-products', [App\Http\Controllers\Admin\DashboardController::class, 'topProducts']);
        Route::get('/recent-orders', [App\Http\Controllers\Admin\DashboardController::class, 'recentOrders']);

        // User Management
        Route::get('/users', [App\Http\Controllers\AdminController::class, 'users']);

        // Product Management
        Route::get('/products', [App\Http\Controllers\Admin\ProductController::class, 'index']);
        Route::post('/products', [App\Http\Controllers\Admin\ProductController::class, 'store']);
        Route::get('/products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'show']);
        Route::put('/products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'update']);
        Route::delete('/products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'destroy']);
        Route::post('/products/bulk-update', [App\Http\Controllers\Admin\ProductController::class, 'bulkUpdate']);

        // Vendor Management
        Route::get('/vendors', [App\Http\Controllers\Admin\VendorController::class, 'index']);
        Route::post('/vendors', [App\Http\Controllers\Admin\VendorController::class, 'store']);
        Route::get('/vendors/{id}', [App\Http\Controllers\Admin\VendorController::class, 'show']);
        Route::put('/vendors/{id}', [App\Http\Controllers\Admin\VendorController::class, 'update']);
        Route::delete('/vendors/{id}', [App\Http\Controllers\Admin\VendorController::class, 'destroy']);
        Route::post('/vendors/{id}/approve', [App\Http\Controllers\Admin\VendorController::class, 'approve']);
        Route::get('/vendors/{id}/products', [App\Http\Controllers\Admin\VendorController::class, 'products']);

        // Category Management
        Route::get('/categories', [App\Http\Controllers\Admin\CategoryController::class, 'index']);
        Route::post('/categories', [App\Http\Controllers\Admin\CategoryController::class, 'store']);
        Route::get('/categories/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'show']);
        Route::put('/categories/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'destroy']);
        Route::post('/categories/reorder', [App\Http\Controllers\Admin\CategoryController::class, 'reorder']);

        // Shipping Management
        Route::get('/shipping/zones', [App\Http\Controllers\Admin\ShippingController::class, 'zones']);
        Route::post('/shipping/zones', [App\Http\Controllers\Admin\ShippingController::class, 'storeZone']);
        Route::put('/shipping/zones/{id}', [App\Http\Controllers\Admin\ShippingController::class, 'updateZone']);
        Route::delete('/shipping/zones/{id}', [App\Http\Controllers\Admin\ShippingController::class, 'destroyZone']);
        Route::get('/shipping/zones/{zoneId}/rules', [App\Http\Controllers\Admin\ShippingController::class, 'zoneRules']);
        Route::post('/shipping/rules', [App\Http\Controllers\Admin\ShippingController::class, 'storeRule']);
        Route::put('/shipping/rules/{id}', [App\Http\Controllers\Admin\ShippingController::class, 'updateRule']);
        Route::delete('/shipping/rules/{id}', [App\Http\Controllers\Admin\ShippingController::class, 'destroyRule']);
        Route::get('/shipping/methods', [App\Http\Controllers\Admin\ShippingController::class, 'methods']);
        Route::post('/shipping/methods', [App\Http\Controllers\Admin\ShippingController::class, 'storeMethod']);

        // Settings Management
        Route::get('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'index']);
        Route::put('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'update']);
        Route::get('/settings/currency', [App\Http\Controllers\Admin\SettingsController::class, 'getCurrency']);
        Route::put('/settings/currency', [App\Http\Controllers\Admin\SettingsController::class, 'updateCurrency']);
        Route::get('/settings/payment-gateway', [App\Http\Controllers\Admin\SettingsController::class, 'getPaymentGateway']);
        Route::put('/settings/payment-gateway', [App\Http\Controllers\Admin\SettingsController::class, 'updatePaymentGateway']);

        // Order Management
        Route::get('/orders', [App\Http\Controllers\Admin\OrderController::class, 'index']);
        Route::get('/orders/statistics', [App\Http\Controllers\Admin\OrderController::class, 'statistics']);
        Route::get('/orders/export', [App\Http\Controllers\Admin\OrderController::class, 'export']);
        Route::get('/orders/{id}', [App\Http\Controllers\Admin\OrderController::class, 'show']);
        Route::put('/orders/{id}/status', [App\Http\Controllers\Admin\OrderController::class, 'updateStatus']);
        Route::put('/orders/{id}/payment-status', [App\Http\Controllers\Admin\OrderController::class, 'updatePaymentStatus']);

        // Review Moderation
        Route::get('/reviews', [App\Http\Controllers\ReviewController::class, 'adminIndex']);
        Route::patch('/reviews/{reviewId}/status', [App\Http\Controllers\ReviewController::class, 'adminUpdateStatus']);
    });
});