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
Route::get('/health', [App\Http\Controllers\ObservabilityController::class, 'health']);

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/register', [App\Http\Controllers\AuthController::class, 'register']);
    Route::post('/auth/register/verify', [App\Http\Controllers\AuthController::class, 'verifyRegistration']);
    Route::post('/auth/register/resend-code', [App\Http\Controllers\AuthController::class, 'resendRegistrationCode']);
    Route::post('/auth/login', [App\Http\Controllers\AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [App\Http\Controllers\AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [App\Http\Controllers\AuthController::class, 'resetPassword']);
});
Route::post('/payments/webhook/{provider}', [App\Http\Controllers\PaymentController::class, 'webhook'])
    ->whereIn('provider', ['dummy', 'paystack', 'flutterwave']);
Route::get('/payments/gateways', [App\Http\Controllers\PaymentController::class, 'gateways']);
Route::get('/locations/countries', [App\Http\Controllers\LocationController::class, 'countries']);
Route::get('/locations/states', [App\Http\Controllers\LocationController::class, 'states']);
Route::get('/locations/cities', [App\Http\Controllers\LocationController::class, 'cities']);
Route::get('/locations/autocomplete', [App\Http\Controllers\LocationController::class, 'autocomplete']);
Route::get('/checkout/cities', [App\Http\Controllers\CheckoutController::class, 'operationalCities']);
Route::get('/checkout/areas', [App\Http\Controllers\CheckoutController::class, 'operationalAreas']);
Route::get('/checkout/dispatch-time-slots', [App\Http\Controllers\CheckoutController::class, 'dispatchTimeSlots']);
Route::get('/settings/public', [App\Http\Controllers\Admin\SettingsController::class, 'publicSettings']);
Route::get('/settings/currency', [App\Http\Controllers\Admin\SettingsController::class, 'getCurrency']);
Route::post('/newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe'])
    ->middleware('throttle:5,1');

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

    Route::middleware('customer')->group(function () {
        // Cart & Checkout
        Route::get('/cart', [App\Http\Controllers\CartController::class, 'show']);
        Route::post('/cart/items', [App\Http\Controllers\CartController::class, 'addItem']);
        Route::put('/cart/items/{id}', [App\Http\Controllers\CartController::class, 'updateItem']);
        Route::delete('/cart/items/{id}', [App\Http\Controllers\CartController::class, 'removeItem']);
        Route::post('/cart/apply-coupon', [App\Http\Controllers\CartController::class, 'applyCoupon']);
        Route::delete('/cart/coupon', [App\Http\Controllers\CartController::class, 'removeCoupon']);
        Route::delete('/cart', [App\Http\Controllers\CartController::class, 'clear']);
        Route::post('/cart/merge', [App\Http\Controllers\CartController::class, 'merge']);

        // Customer addresses
        Route::get('/addresses', [App\Http\Controllers\AddressController::class, 'index']);
        Route::post('/addresses', [App\Http\Controllers\AddressController::class, 'store']);
        Route::put('/addresses/{id}', [App\Http\Controllers\AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [App\Http\Controllers\AddressController::class, 'destroy']);
        Route::patch('/addresses/{id}/default', [App\Http\Controllers\AddressController::class, 'setDefault']);

        Route::post('/checkout/quote-shipping', [App\Http\Controllers\CheckoutController::class, 'quoteShipping']);
        Route::post('/checkout/place-order', [App\Http\Controllers\CheckoutController::class, 'placeOrder']);
        Route::post('/payments/orders/{orderId}/initialize', [App\Http\Controllers\PaymentController::class, 'initialize']);
        Route::post('/payments/{paymentId}/verify', [App\Http\Controllers\PaymentController::class, 'verify']);

        // Customer Orders
        Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index']);
        Route::get('/orders/{id}', [App\Http\Controllers\OrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [App\Http\Controllers\OrderController::class, 'cancel']);

        Route::get('/referrals/me', [App\Http\Controllers\ReferralController::class, 'me']);
        Route::get('/referrals/rewards', [App\Http\Controllers\ReferralController::class, 'rewards']);
    });

    Route::prefix('dispatch')->middleware('role:Dispatch Rider')->group(function () {
        Route::get('/assignments', [App\Http\Controllers\DispatchAssignmentController::class, 'myAssignments']);
        Route::put('/assignments/{assignmentId}', [App\Http\Controllers\DispatchAssignmentController::class, 'update']);
    });

    // Vendor/Admin Product Management
    Route::prefix('vendor')->middleware('vendor')->group(function () {
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
    Route::prefix('shipping')->middleware('admin')->group(function () {
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
        Route::get('/stock-alerts', [App\Http\Controllers\Admin\DashboardController::class, 'stockAlerts']);

        // User Management
        Route::get('/users', [App\Http\Controllers\AdminController::class, 'users']);
        Route::get('/roles-permissions', [App\Http\Controllers\Admin\RolePermissionController::class, 'index'])->middleware('can:role.view');
        Route::post('/roles', [App\Http\Controllers\Admin\RolePermissionController::class, 'storeRole'])->middleware('can:role.manage');
        Route::put('/roles/{id}/permissions', [App\Http\Controllers\Admin\RolePermissionController::class, 'updateRolePermissions'])->middleware('can:role.manage');
        Route::get('/role-users', [App\Http\Controllers\Admin\RolePermissionController::class, 'users'])->middleware('can:user.view');
        Route::put('/users/{id}/roles', [App\Http\Controllers\Admin\RolePermissionController::class, 'updateUserRoles'])->middleware('can:role.manage');

        // Product Management
        Route::get('/products', [App\Http\Controllers\Admin\ProductController::class, 'index']);
        Route::post('/products', [App\Http\Controllers\Admin\ProductController::class, 'store']);
        Route::get('/products/sku-search', [App\Http\Controllers\Admin\ProductController::class, 'skuSearch']);
        Route::get('/products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'show']);
        Route::put('/products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'update']);
        Route::delete('/products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'destroy']);
        Route::post('/products/bulk-update', [App\Http\Controllers\Admin\ProductController::class, 'bulkUpdate']);
        Route::post('/products/{id}/images', [App\Http\Controllers\Admin\ProductController::class, 'uploadImages'])->middleware('can:product-image.manage');
        Route::put('/products/{id}/images/order', [App\Http\Controllers\Admin\ProductController::class, 'reorderImages'])->middleware('can:product-image.manage');
        Route::put('/products/{id}/images/{imageId}/primary', [App\Http\Controllers\Admin\ProductController::class, 'setPrimaryImage'])->middleware('can:product-image.manage');
        Route::delete('/products/{id}/images/{imageId}', [App\Http\Controllers\Admin\ProductController::class, 'deleteImage'])->middleware('can:product-image.manage');
        Route::post('/products/{id}/skus/{skuId}/images', [App\Http\Controllers\Admin\ProductController::class, 'uploadSkuImages'])->middleware('can:product-option.update');
        Route::put('/products/{id}/skus/{skuId}/images/order', [App\Http\Controllers\Admin\ProductController::class, 'reorderSkuImages'])->middleware('can:product-option.update');
        Route::put('/products/{id}/skus/{skuId}/images/{imageId}/primary', [App\Http\Controllers\Admin\ProductController::class, 'setPrimarySkuImage'])->middleware('can:product-option.update');
        Route::delete('/products/{id}/skus/{skuId}/images/{imageId}', [App\Http\Controllers\Admin\ProductController::class, 'deleteSkuImage'])->middleware('can:product-option.update');

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
        Route::post('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'update']);
        Route::put('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'update']);
        Route::post('/settings/mail/test', [App\Http\Controllers\Admin\SettingsController::class, 'testMail']);
        Route::get('/settings/currency', [App\Http\Controllers\Admin\SettingsController::class, 'getCurrency']);
        Route::put('/settings/currency', [App\Http\Controllers\Admin\SettingsController::class, 'updateCurrency']);

        // Newsletter subscribers
        Route::get('/newsletter-subscribers', [App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'index']);
        Route::get('/newsletter-subscribers/export', [App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'export']);
        Route::post('/newsletter-subscribers/send', [App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'send']);
        Route::patch('/newsletter-subscribers/{subscriber}/status', [App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'updateStatus']);
        Route::delete('/newsletter-subscribers/{subscriber}', [App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'destroy']);

        // Referrals
        Route::get('/referrals', [App\Http\Controllers\Admin\ReferralController::class, 'index']);
        Route::get('/referral-rewards', [App\Http\Controllers\Admin\ReferralController::class, 'rewards']);
        Route::patch('/referral-rewards/{id}/status', [App\Http\Controllers\Admin\ReferralController::class, 'updateRewardStatus']);
        Route::get('/referral-settings', [App\Http\Controllers\Admin\ReferralController::class, 'settings']);
        Route::put('/referral-settings', [App\Http\Controllers\Admin\ReferralController::class, 'updateSettings']);

        // Payment Gateways Management
        Route::get('/payment-gateways', [App\Http\Controllers\Admin\PaymentGatewayController::class, 'index']);
        Route::put('/payment-gateways/{provider}', [App\Http\Controllers\Admin\PaymentGatewayController::class, 'update']);
        Route::post('/payment-gateways/reorder', [App\Http\Controllers\Admin\PaymentGatewayController::class, 'reorder']);
        Route::post('/payment-gateways/{provider}/set-default', [App\Http\Controllers\Admin\PaymentGatewayController::class, 'setDefault']);

        // Order Management
        Route::get('/orders', [App\Http\Controllers\Admin\OrderController::class, 'index']);
        Route::post('/orders', [App\Http\Controllers\Admin\AdminOrderCreationController::class, 'store'])->middleware('can:admin-order.create');
        Route::get('/orders/statistics', [App\Http\Controllers\Admin\OrderController::class, 'statistics']);
        Route::get('/orders/export', [App\Http\Controllers\Admin\OrderController::class, 'export']);
        Route::get('/orders/customers', [App\Http\Controllers\Admin\AdminOrderCreationController::class, 'customers'])->middleware('can:admin-order.create');
        Route::get('/orders/products', [App\Http\Controllers\Admin\AdminOrderCreationController::class, 'products'])->middleware('can:admin-order.create');
        Route::get('/orders/{id}', [App\Http\Controllers\Admin\OrderController::class, 'show']);
        Route::get('/orders/{id}/terminal-receipt', [App\Http\Controllers\Admin\OrderController::class, 'terminalReceipt'])->middleware('can:order.receipt.download');
        Route::put('/orders/{id}/status', [App\Http\Controllers\Admin\OrderController::class, 'updateStatus']);
        Route::put('/orders/{id}/payment-status', [App\Http\Controllers\Admin\OrderController::class, 'updatePaymentStatus']);
        Route::put('/orders/{id}/delivery-assignment', [App\Http\Controllers\Admin\OrderController::class, 'assignDeliveryPartner']);
        Route::put('/orders/{id}/dispatch-rider', [App\Http\Controllers\Admin\OrderController::class, 'assignDispatchRider'])->middleware('can:dispatch-assignment.manage');
        Route::put('/orders/{id}/delivery-status', [App\Http\Controllers\Admin\OrderController::class, 'updateDeliveryStatus']);

        // Delivery partners
        Route::get('/delivery-partners', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'index']);
        Route::post('/delivery-partners', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'store']);
        Route::put('/delivery-partners/{id}', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'update']);
        Route::patch('/delivery-partners/{id}/status', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'updateStatus']);

        // Dispatch riders
        Route::get('/dispatch-riders', [App\Http\Controllers\Admin\DispatchRiderController::class, 'index'])->middleware('can:dispatch-rider.view');
        Route::post('/dispatch-riders', [App\Http\Controllers\Admin\DispatchRiderController::class, 'store'])->middleware('can:dispatch-rider.create');
        Route::get('/dispatch-riders/{id}', [App\Http\Controllers\Admin\DispatchRiderController::class, 'show'])->middleware('can:dispatch-rider.view');
        Route::post('/dispatch-riders/{id}', [App\Http\Controllers\Admin\DispatchRiderController::class, 'update'])->middleware('can:dispatch-rider.update');
        Route::put('/dispatch-riders/{id}', [App\Http\Controllers\Admin\DispatchRiderController::class, 'update'])->middleware('can:dispatch-rider.update');
        Route::patch('/dispatch-riders/{id}/status', [App\Http\Controllers\Admin\DispatchRiderController::class, 'updateStatus'])->middleware('can:dispatch-rider.update');

        // Dispatch time slots
        Route::get('/dispatch-time-slots', [App\Http\Controllers\Admin\DispatchTimeSlotController::class, 'index'])->middleware('can:dispatch-time.view');
        Route::post('/dispatch-time-slots', [App\Http\Controllers\Admin\DispatchTimeSlotController::class, 'store'])->middleware('can:dispatch-time.create');
        Route::put('/dispatch-time-slots/{id}', [App\Http\Controllers\Admin\DispatchTimeSlotController::class, 'update'])->middleware('can:dispatch-time.update');
        Route::delete('/dispatch-time-slots/{id}', [App\Http\Controllers\Admin\DispatchTimeSlotController::class, 'destroy'])->middleware('can:dispatch-time.delete');

        // Cities of operation
        Route::get('/operation-cities', [App\Http\Controllers\Admin\OperationCityController::class, 'index'])->middleware('can:city.view');
        Route::post('/operation-cities', [App\Http\Controllers\Admin\OperationCityController::class, 'store'])->middleware('can:city.create');
        Route::put('/operation-cities/{id}', [App\Http\Controllers\Admin\OperationCityController::class, 'update'])->middleware('can:city.update');
        Route::delete('/operation-cities/{id}', [App\Http\Controllers\Admin\OperationCityController::class, 'destroy'])->middleware('can:city.delete');

        // Areas of operation
        Route::get('/operation-areas', [App\Http\Controllers\Admin\OperationAreaController::class, 'index'])->middleware('can:area.view');
        Route::post('/operation-areas', [App\Http\Controllers\Admin\OperationAreaController::class, 'store'])->middleware('can:area.create');
        Route::put('/operation-areas/{id}', [App\Http\Controllers\Admin\OperationAreaController::class, 'update'])->middleware('can:area.update');
        Route::delete('/operation-areas/{id}', [App\Http\Controllers\Admin\OperationAreaController::class, 'destroy'])->middleware('can:area.delete');

        // Inventory and reporting
        Route::get('/inventory/skus', [App\Http\Controllers\Admin\InventoryController::class, 'stockSkus'])->middleware('can:inventory.view');
        Route::get('/inventory/batches', [App\Http\Controllers\Admin\InventoryController::class, 'index'])->middleware('can:inventory.view');
        Route::post('/inventory/add-stock', [App\Http\Controllers\Admin\InventoryController::class, 'addStock'])->middleware('can:inventory.add-stock');
        Route::get('/inventory/ledger', [App\Http\Controllers\Admin\InventoryController::class, 'ledger'])->middleware('can:inventory.ledger.view');
        Route::get('/inventory/expiry-alerts', [App\Http\Controllers\Admin\InventoryController::class, 'expiryAlerts'])->middleware('can:inventory.expiry.manage');
        Route::put('/inventory/batches/{batchId}/expiry', [App\Http\Controllers\Admin\InventoryController::class, 'updateBatchExpiry'])->middleware('can:inventory.expiry.manage');
        Route::get('/reports/profit-margins', [App\Http\Controllers\Admin\InventoryController::class, 'profitMargins'])->middleware('can:profit-report.view');

        // Review Moderation
        Route::get('/reviews', [App\Http\Controllers\ReviewController::class, 'adminIndex']);
        Route::patch('/reviews/{reviewId}/status', [App\Http\Controllers\ReviewController::class, 'adminUpdateStatus']);
    });
});
