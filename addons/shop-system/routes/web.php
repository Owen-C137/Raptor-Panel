<?php

use Illuminate\Support\Facades\Route;
use PterodactylAddons\ShopSystem\Http\Controllers\ShopController;
use PterodactylAddons\ShopSystem\Http\Controllers\CheckoutController;
use PterodactylAddons\ShopSystem\Http\Controllers\OrderController;
use PterodactylAddons\ShopSystem\Http\Controllers\WalletController;
use PterodactylAddons\ShopSystem\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Shop Asset Routes
|--------------------------------------------------------------------------
|
| These routes serve CSS and JS assets directly from the addon directory
| to keep everything self-contained within the addon structure.
|
*/

// Serve shop CSS
Route::get('shop/assets/css/shop.css', function () {
    $path = base_path('addons/shop-system/resources/assets/css/shop.css');
    if (!file_exists($path)) {
        abort(404);
    }
    return response(file_get_contents($path))
        ->header('Content-Type', 'text/css');
})->name('shop.assets.css');

// Serve shop JS
Route::get('shop/assets/js/shop.js', function () {
    $path = base_path('addons/shop-system/resources/assets/js/shop.js');
    if (!file_exists($path)) {
        abort(404);
    }
    return response(file_get_contents($path))
        ->header('Content-Type', 'application/javascript');
})->name('shop.assets.js');

/*
|--------------------------------------------------------------------------
| Shop Web Routes
|--------------------------------------------------------------------------
|
| Here are the web routes for the shop system. These routes handle
| the user-facing shop interface with proper authentication and middleware.
|
*/

// Public shop routes (no authentication required)
Route::prefix('shop')->name('shop.')->group(function () {
    // Main shop catalog
    Route::get('/', [ShopController::class, 'index'])->name('index');
    
    // Category routes (new approach)
    Route::get('/category/{category}', [ShopController::class, 'showCategory'])->name('category');
    Route::get('/category/{category}/plans', [ShopController::class, 'getCategoryPlans'])->name('category.plans');
    
    // Plan detail route
    Route::get('/plan/{plan}', [ShopController::class, 'showPlan'])->name('plan');
    
    // Legacy product routes (for backward compatibility)
    Route::get('/product/{product}', [ShopController::class, 'showProduct'])->name('product');
    
    // Payment webhooks (public endpoints)
    Route::post('/webhooks/stripe', [WebhookController::class, 'stripe'])->name('webhooks.stripe');
    Route::post('/webhooks/paypal', [WebhookController::class, 'paypal'])->name('webhooks.paypal');
    Route::post('/webhook/{gateway}', [WebhookController::class, 'handle'])->name('webhook');
    
    // Payment callback routes (public endpoints for PayPal/Stripe returns)
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/{gateway}/return/{order:uuid}', [CheckoutController::class, 'paymentReturn'])->name('return');
        Route::get('/{gateway}/cancel/{order:uuid}', [CheckoutController::class, 'paymentCancel'])->name('cancel');
    });
});

// Authenticated shop routes
Route::prefix('shop')->name('shop.')->middleware(['auth', 'shop.enabled'])->group(function () {
    
    // Cart management (database-based, auth required)
    Route::get('/cart', [ShopController::class, 'cart'])->name('cart');
    Route::post('/cart/add', [ShopController::class, 'addToCart'])->name('cart.add');
    Route::delete('/cart/remove', [ShopController::class, 'removeFromCart'])->name('cart.remove');
    Route::put('/cart/update', [ShopController::class, 'updateCartQuantity'])->name('cart.update');
    Route::delete('/cart/clear', [ShopController::class, 'clearCart'])->name('cart.clear');
    Route::get('/cart/summary', [ShopController::class, 'getCartSummary'])->name('cart.summary');
    Route::post('/cart/promo/apply', [ShopController::class, 'applyPromoCode'])->name('cart.promo.apply');
    Route::delete('/cart/promo/remove', [ShopController::class, 'removePromoCode'])->name('cart.promo.remove');
    
    // Checkout process
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::get('/summary', [CheckoutController::class, 'getSummary'])->name('summary');
        Route::post('/process', [CheckoutController::class, 'process'])->name('process');
        Route::post('/coupon/apply', [CheckoutController::class, 'applyCoupon'])->name('coupon.apply');
        Route::delete('/coupon/remove', [CheckoutController::class, 'removeCoupon'])->name('coupon.remove');
        
        // Wallet payment completion (deferred processing like PayPal)
        Route::get('/wallet/complete/{order:uuid}', [CheckoutController::class, 'completeWalletPayment'])->name('wallet.complete');
    });
    
    // Order management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order:uuid}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order:uuid}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::post('/{order:uuid}/renew', [OrderController::class, 'renew'])->name('renew');
        Route::post('/{order:uuid}/payment', [OrderController::class, 'processPayment'])->name('payment');
        Route::get('/{order:uuid}/invoice', [OrderController::class, 'downloadInvoice'])->name('invoice');
        Route::post('/{order:uuid}/create-server', [OrderController::class, 'createServer'])->name('create-server');
        
        // Admin-only order management routes
        Route::middleware('admin')->group(function () {
            Route::post('/{order:uuid}/suspend', [OrderController::class, 'suspend'])->name('suspend');
            Route::post('/{order:uuid}/unsuspend', [OrderController::class, 'unsuspend'])->name('unsuspend');
        });
    });
    
    // Wallet management
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::get('/add-funds', [WalletController::class, 'addFunds'])->name('add-funds');
        Route::post('/add-funds', [WalletController::class, 'processAddFunds'])->name('add-funds.process');
        Route::get('/balance', [WalletController::class, 'getBalance'])->name('balance');
        Route::get('/transactions', [WalletController::class, 'getTransactions'])->name('transactions');
        Route::post('/auto-topup', [WalletController::class, 'autoTopup'])->name('auto-topup');
        Route::get('/export', [WalletController::class, 'exportTransactions'])->name('export');
        
        // Wallet deposit completion callbacks
        Route::get('/deposit/stripe/return', [WalletController::class, 'stripeDepositReturn'])->name('deposit.stripe.return');
        Route::get('/deposit/paypal/return', [WalletController::class, 'paypalDepositReturn'])->name('deposit.paypal.return');
        Route::get('/deposit/paypal/cancel', [WalletController::class, 'paypalDepositCancel'])->name('deposit.paypal.cancel');
        
        // Optional wallet transfer feature
        Route::middleware('shop.transfers_enabled')->group(function () {
            Route::post('/transfer', [WalletController::class, 'transfer'])->name('transfer');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Shop Middleware Definitions
|--------------------------------------------------------------------------
*/

// Custom middleware for shop functionality
Route::middleware(['web'])->group(function () {
    // Shop enabled check middleware
    Route::aliasMiddleware('shop.enabled', function ($request, $next) {
        if (!config('shop.enabled', false)) {
            abort(503, config('shop.maintenance_message', 'Shop is temporarily unavailable.'));
        }
        return $next($request);
    });
    
    // Shop transfers enabled check
    Route::aliasMiddleware('shop.transfers_enabled', function ($request, $next) {
        if (!config('shop.wallet.transfers_enabled', false)) {
            abort(404);
        }
        return $next($request);
    });
    
    // Admin check middleware
    Route::aliasMiddleware('admin', function ($request, $next) {
        if (!auth()->check() || !auth()->user()->root_admin) {
            abort(403, 'Access denied. Administrative privileges required.');
        }
        return $next($request);
    });
});
