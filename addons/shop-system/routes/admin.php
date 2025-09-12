<?php

use Illuminate\Support\Facades\Route;
use PterodactylAddons\ShopSystem\Http\Controllers\Admin\DashboardController;
use PterodactylAddons\ShopSystem\Http\Controllers\Admin\DashboardController as AdminDashboardController;

use PterodactylAddons\ShopSystem\Http\Controllers\Admin\PlanController;
use PterodactylAddons\ShopSystem\Http\Controllers\Admin\OrderController;  
use PterodactylAddons\ShopSystem\Http\Controllers\Admin\CategoryController;
use PterodactylAddons\ShopSystem\Http\Controllers\Admin\CouponController;
use PterodactylAddons\ShopSystem\Http\Controllers\Admin\PaymentManagementController;
use PterodactylAddons\ShopSystem\Http\Controllers\Admin\WalletManagementController;
use PterodactylAddons\ShopSystem\Http\Controllers\Admin\AnalyticsController;
use PterodactylAddons\ShopSystem\Http\Controllers\Admin\ReportsController;
use PterodactylAddons\ShopSystem\Http\Controllers\Admin\SettingsController;

/*
|--------------------------------------------------------------------------
| Admin Shop Routes
|--------------------------------------------------------------------------
|
| These routes handle the administrative interface for the shop system.
| All routes require root admin privileges and are integrated with the
| existing Pterodactyl admin panel structure.
|
*/

Route::name('admin.shop.')->middleware(['auth', 'admin'])->group(function () {
    
    // Main dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    
    // Category Management
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}', [CategoryController::class, 'show'])->name('show');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Plan Management (now category-based)
    Route::prefix('categories/{category}/plans')->name('category.plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/create', [PlanController::class, 'create'])->name('create');
        Route::post('/', [PlanController::class, 'store'])->name('store');
        Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy');
    });
    
    // Standalone Plan Management
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/create', [PlanController::class, 'create'])->name('create');
        Route::post('/', [PlanController::class, 'store'])->name('store');
        Route::get('/{plan}', [PlanController::class, 'show'])->name('show');
        Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{plan}/duplicate', [PlanController::class, 'duplicate'])->name('duplicate');
    });
    
    // Order Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        Route::post('/{order}/suspend', [OrderController::class, 'suspend'])->name('suspend');
        Route::post('/{order}/unsuspend', [OrderController::class, 'unsuspend'])->name('unsuspend');
        Route::post('/{order}/terminate', [OrderController::class, 'terminate'])->name('terminate');
        Route::post('/{order}/renew', [OrderController::class, 'renew'])->name('renew');
        Route::post('/{order}/notes', [OrderController::class, 'addNote'])->name('add-note');
        Route::post('/{order}/update-status', [OrderController::class, 'updateStatus'])->name('update-status');
        
        // Bulk actions
        Route::post('/bulk/suspend', [OrderController::class, 'bulkSuspend'])->name('bulk.suspend');
        Route::post('/bulk/unsuspend', [OrderController::class, 'bulkUnsuspend'])->name('bulk.unsuspend');
        Route::post('/bulk/terminate', [OrderController::class, 'bulkTerminate'])->name('bulk.terminate');
    });
    
    // Coupon Management
    Route::prefix('coupons')->name('coupons.')->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::get('/create', [CouponController::class, 'create'])->name('create');
        Route::post('/', [CouponController::class, 'store'])->name('store');
        Route::get('/{coupon}', [CouponController::class, 'show'])->name('show');
        Route::get('/{coupon}/edit', [CouponController::class, 'edit'])->name('edit');
        Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
        Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');
        Route::post('/{coupon}/toggle-status', [CouponController::class, 'toggle'])->name('toggle-status');
        Route::get('/{coupon}/usage', [CouponController::class, 'usage'])->name('usage');
        Route::get('/{coupon}/duplicate', [CouponController::class, 'duplicate'])->name('duplicate');
    });
    
    // Payment Management
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentManagementController::class, 'index'])->name('index');
        Route::get('/{payment}', [PaymentManagementController::class, 'show'])->name('show');
        Route::post('/{payment}/refund', [PaymentManagementController::class, 'refund'])->name('refund');
        Route::get('/gateway/{gateway}/transactions', [PaymentManagementController::class, 'gatewayTransactions'])->name('gateway.transactions');
    });
    
    // Analytics and Reports
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');
        Route::get('/orders', [AnalyticsController::class, 'orders'])->name('orders');
        Route::get('/customers', [AnalyticsController::class, 'customers'])->name('customers');
        Route::get('/plans', [AnalyticsController::class, 'plans'])->name('plans');
        Route::get('/export', [AnalyticsController::class, 'export'])->name('export');
    });
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::get('/revenue', [ReportsController::class, 'revenue'])->name('revenue');
        Route::get('/orders', [ReportsController::class, 'orders'])->name('orders');
        Route::get('/customers', [ReportsController::class, 'customers'])->name('customers');
        Route::get('/export/{type}', [ReportsController::class, 'export'])->name('export');
    });
    
    // Settings and Configuration
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::put('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');
        
        Route::get('/payment-gateways', [SettingsController::class, 'paymentGateways'])->name('payment-gateways');
        Route::put('/payment-gateways', [SettingsController::class, 'updatePaymentGateways'])->name('payment-gateways.update');
        
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
        
        Route::get('/billing', [SettingsController::class, 'billing'])->name('billing');
        Route::put('/billing', [SettingsController::class, 'updateBilling'])->name('billing.update');
        
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::put('/security', [SettingsController::class, 'updateSecurity'])->name('security.update');
        
        // System maintenance
        Route::post('/maintenance/toggle', [SettingsController::class, 'toggleMaintenance'])->name('maintenance.toggle');
        Route::post('/cache/clear', [SettingsController::class, 'clearCache'])->name('cache.clear');
        Route::post('/jobs/process', [SettingsController::class, 'processJobs'])->name('jobs.process');
    });
    
    // User wallet management
    Route::prefix('wallets')->name('wallets.')->group(function () {
        Route::get('/', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\WalletManagementController::class, 'index'])->name('index');
        Route::get('/{user}', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\WalletManagementController::class, 'show'])->name('show');
        Route::post('/{user}/credit', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\WalletManagementController::class, 'addCredit'])->name('credit');
        Route::post('/{user}/debit', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\WalletManagementController::class, 'deductCredit'])->name('debit');
        Route::get('/{user}/transactions', [\PterodactylAddons\ShopSystem\Http\Controllers\Admin\WalletManagementController::class, 'transactions'])->name('transactions');
    });
    
    // AJAX Routes for Admin Interface
    Route::prefix('ajax')->name('ajax.')->group(function () {
        
        // Dashboard widgets
        Route::get('/dashboard/stats', [AdminDashboardController::class, 'getStats'])->name('dashboard.stats');
        Route::get('/dashboard/recent-orders', [AdminDashboardController::class, 'getRecentOrders'])->name('dashboard.recent-orders');
        Route::get('/dashboard/revenue-chart', [AdminDashboardController::class, 'getRevenueChart'])->name('dashboard.revenue-chart');
        Route::get('/dashboard/top-plans', [AdminDashboardController::class, 'getTopPlans'])->name('dashboard.top-plans');
        
        // Category quick actions
        Route::post('/categories/{category}/toggle', [CategoryController::class, 'toggleStatus'])->name('categories.toggle');
        
        // Order quick actions
        Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::get('/orders/search', [OrderController::class, 'search'])->name('orders.search');
        
    });
});


