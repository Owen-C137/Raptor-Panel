<?php

namespace PterodactylAddons\ShopSystem\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use PterodactylAddons\ShopSystem\Http\Middleware\InjectShopNavigation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class ShopNavigationServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Register middleware for navigation injection
        $this->app[Kernel::class]->pushMiddleware(InjectShopNavigation::class);
        
        // Share shop navigation data with all views
        $this->shareNavigationData();
        
        // Shop routes are registered in main ShopServiceProvider
        // $this->registerShopRoutes(); // Disabled to avoid duplicates
    }
    
    private function shareNavigationData()
    {
        View::composer('*', function ($view) {
            // Share cart count for navigation
            if (auth()->check()) {
                $cart = session('shop_cart', []);
                $cartCount = is_array($cart) ? collect($cart)->sum('quantity') : $cart->sum('quantity');
                $view->with('shopCartCount', $cartCount);
            }
            
            // Share shop configuration
            $view->with('shopConfig', [
                'enabled' => config('shop.enabled', false),
                'currency' => config('shop.currency', 'USD'),
                'currency_symbol' => config('shop.currency_symbol', '$'),
            ]);
        });
    }
    
    private function registerShopRoutes()
    {
        // Frontend shop routes
        Route::middleware(['web', 'auth'])
            ->prefix('shop')
            ->name('shop.')
            ->group(function () {
                // Main shop pages
                Route::get('/', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@index')->name('index');
                Route::get('/products', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@products')->name('products');
                Route::get('/product/{product}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@show')->name('product.show');
                Route::get('/category/{category}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@category')->name('category');
                Route::get('/search', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@search')->name('search');
                
                // Cart management
                Route::prefix('cart')->name('cart.')->group(function () {
                    Route::get('/', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@cart')->name('index');
                    Route::post('/add', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@addToCart')->name('add');
                    Route::patch('/update/{index}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@updateCart')->name('update');
                    Route::delete('/remove/{index}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@removeFromCart')->name('remove');
                    Route::delete('/clear', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@clearCart')->name('clear');
                });
                
                // Checkout process
                Route::prefix('checkout')->name('checkout.')->group(function () {
                    Route::get('/', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\CheckoutController@index')->name('index');
                    Route::post('/process', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\CheckoutController@process')->name('process');
                    Route::get('/success/{order}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\CheckoutController@success')->name('success');
                    Route::get('/method/{method}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\CheckoutController@selectMethod')->name('method');
                    Route::get('/retry/{order}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\CheckoutController@retry')->name('retry');
                });
                
                // Order management
                Route::prefix('orders')->name('orders.')->group(function () {
                    Route::get('/', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\OrderController@index')->name('index');
                    Route::get('/{order}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\OrderController@show')->name('show');
                    Route::post('/{order}/renew', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\OrderController@renew')->name('renew');
                    Route::post('/{order}/cancel', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\OrderController@cancel')->name('cancel');
                    Route::post('/{order}/upgrade', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\OrderController@upgrade')->name('upgrade');
                });
                
                // Wallet management
                Route::prefix('wallet')->name('wallet.')->group(function () {
                    Route::get('/', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\WalletController@index')->name('index');
                    Route::post('/topup', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\WalletController@topup')->name('topup');
                    Route::get('/topup/{amount?}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\WalletController@topupForm')->name('topup.form');
                    Route::get('/transactions', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\WalletController@transactions')->name('transactions');
                    Route::post('/transfer', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\WalletController@transfer')->name('transfer');
                });
                
                // Dashboard
                Route::get('/dashboard', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\ShopController@dashboard')->name('dashboard');
            });
        
        // Admin shop routes
        Route::middleware(['web', 'auth', 'admin'])
            ->prefix('admin/shop')
            ->name('admin.shop.')
            ->group(function () {
                // Dashboard
                Route::get('/', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\ShopController@dashboard')->name('index');
                
                // Categories management
                Route::resource('categories', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\CategoryController');
                Route::post('categories/{category}/toggle', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\CategoryController@toggle')->name('categories.toggle');
                
                // Plans management (now category-based)
                Route::resource('plans', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\PlanController');
                Route::post('plans/{plan}/toggle', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\PlanController@toggle')->name('plans.toggle');
                
                // Orders management
                Route::resource('orders', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\OrderController')
                    ->only(['index', 'show', 'update', 'destroy']);
                Route::post('orders/{order}/process', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\OrderController@process')->name('orders.process');
                Route::post('orders/{order}/suspend', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\OrderController@suspend')->name('orders.suspend');
                Route::post('orders/{order}/unsuspend', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\OrderController@unsuspend')->name('orders.unsuspend');
                Route::post('orders/{order}/terminate', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\OrderController@terminate')->name('orders.terminate');
                
                // Payments management
                Route::get('payments', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\PaymentController@index')->name('payments.index');
                Route::get('payments/{payment}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\PaymentController@show')->name('payments.show');
                Route::post('payments/{payment}/refund', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\PaymentController@refund')->name('payments.refund');
                
                // Coupons management
                Route::resource('coupons', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\CouponController');
                Route::post('coupons/{coupon}/toggle', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\CouponController@toggle')->name('coupons.toggle');
                
                // Settings
                Route::get('settings', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\SettingsController@index')->name('settings.index');
                Route::post('settings', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\SettingsController@update')->name('settings.update');
                
                // Reports
                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('revenue', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\ReportsController@revenue')->name('revenue');
                    Route::get('orders', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\ReportsController@orders')->name('orders');
                    Route::get('customers', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\ReportsController@customers')->name('customers');
                    Route::get('export/{type}', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin\\ReportsController@export')->name('export');
                });
            });
        
        // API routes
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/shop')
            ->name('api.shop.')
            ->group(function () {
                Route::get('products', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Api\\ShopController@products')->name('products');
                Route::get('orders', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Api\\ShopController@orders')->name('orders');
                Route::get('wallet', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\Api\\ShopController@wallet')->name('wallet');
            });
        
        // Webhook routes (no auth required)
        Route::prefix('webhooks/shop')
            ->name('webhooks.shop.')
            ->group(function () {
                Route::post('stripe', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\WebhookController@stripe')->name('stripe');
                Route::post('paypal', 'PterodactylAddons\\ShopSystem\\Http\\Controllers\\WebhookController@paypal')->name('paypal');
            });
    }
}
