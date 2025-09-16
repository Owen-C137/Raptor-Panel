<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shop API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the shop system. These routes provide
| RESTful endpoints for external integration and third-party applications.
| Note: Product-based endpoints have been removed in favor of category-based system.
|
*/

Route::prefix('api/shop')->name('api.shop.')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    
    // Health check and status
    Route::get('/health', function () {
        return response()->json(['status' => 'OK', 'timestamp' => now()]);
    })->name('health');
    
    // Category catalog endpoints
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', function () {
            $categories = app(\PterodactylAddons\ShopSystem\Repositories\ShopCategoryRepository::class)
                ->getVisibleCategories();
            return response()->json(['categories' => $categories]);
        })->name('index');
    });
});

/*
|--------------------------------------------------------------------------
| Rate Limited Public API Endpoints
|--------------------------------------------------------------------------
|
| These endpoints have more restrictive rate limiting for public access
| and are typically used for webhook verification or status checks.
|
*/

Route::prefix('api/shop/public')->name('api.shop.public.')->middleware(['throttle:30,1'])->group(function () {
    Route::get('/status', function () {
        return response()->json([
            'shop_enabled' => config('shop.enabled', false),
            'maintenance_mode' => config('shop.maintenance_mode', false),
            'version' => \PterodactylAddons\ShopSystem\Services\VersionService::getVersion(),
            'timestamp' => now()->toISOString(),
        ]);
    })->name('status');
    
    Route::get('/categories/featured', function () {
        $categories = app(\PterodactylAddons\ShopSystem\Repositories\ShopCategoryRepository::class)
            ->getVisibleCategories(6);
            
        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    })->name('categories.featured');
});

/*
|--------------------------------------------------------------------------
| Webhook Endpoints
|--------------------------------------------------------------------------
|
| These endpoints handle payment gateway webhooks and callbacks.
| They are excluded from authentication middleware for external access.
|
*/

Route::prefix('api/shop/webhooks')->name('api.shop.webhooks.')->middleware(['throttle:60,1'])->group(function () {
    
    // Stripe webhooks
    Route::post('/stripe', [\PterodactylAddons\ShopSystem\Http\Controllers\WebhookController::class, 'stripeWebhook'])->name('stripe');
    
    // PayPal webhooks
    Route::post('/paypal', [\PterodactylAddons\ShopSystem\Http\Controllers\WebhookController::class, 'paypalWebhook'])->name('paypal');
    
    // Wallet-specific webhooks (if needed for additional processing)
    Route::post('/stripe/wallet', [\PterodactylAddons\ShopSystem\Http\Controllers\WalletController::class, 'stripeWebhook'])->name('stripe.wallet');
    Route::post('/paypal/wallet', [\PterodactylAddons\ShopSystem\Http\Controllers\WalletController::class, 'paypalWebhook'])->name('paypal.wallet');
});

/*
|--------------------------------------------------------------------------
| Administrative API Endpoints
|--------------------------------------------------------------------------
|
| These endpoints are for administrative functions and require elevated
| privileges. They are separate from user-facing API endpoints.
|
*/

Route::prefix('api/shop/admin')->name('api.shop.admin.')->middleware(['auth:sanctum', 'admin', 'throttle:120,1'])->group(function () {
    
    // Category management
    
    
    // Order management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\OrderController::class, 'show'])->name('show');
        Route::post('/{order}/suspend', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\OrderController::class, 'suspend'])->name('suspend');
        Route::post('/{order}/unsuspend', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\OrderController::class, 'unsuspend'])->name('unsuspend');
        Route::post('/{order}/terminate', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\OrderController::class, 'terminate'])->name('terminate');
    });
    
    // Coupon management
    Route::apiResource('coupons', \PterodactylAddons\ShopSystem\Http\Controllers\Admin\CouponController::class);
    
    // Payment management
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\PaymentController::class, 'index'])->name('index');
        Route::get('/{payment}', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\PaymentController::class, 'show'])->name('show');
        Route::post('/{payment}/refund', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\PaymentController::class, 'refund'])->name('refund');
    });
    
    // Analytics and statistics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/dashboard', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\AnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/revenue', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\AnalyticsController::class, 'revenue'])->name('revenue');
        Route::get('/orders', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\AnalyticsController::class, 'orders'])->name('orders');
        Route::get('/customers', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\AnalyticsController::class, 'customers'])->name('customers');
    });
    
    // System management
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/status', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\SystemController::class, 'status'])->name('status');
        Route::post('/maintenance', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\SystemController::class, 'toggleMaintenance'])->name('maintenance');
        Route::post('/cache/clear', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\SystemController::class, 'clearCache'])->name('cache.clear');
    });
});

/*
|--------------------------------------------------------------------------
| API Middleware Definitions
|--------------------------------------------------------------------------
*/

// Admin API middleware
Route::aliasMiddleware('admin', function ($request, $next) {
    if (!auth()->check() || !auth()->user()->root_admin) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    return $next($request);
});
