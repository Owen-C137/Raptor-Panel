# Pterodactyl Shop System - Addon/Plugin Implementation Plan

## 🎯 **CRITICAL: ADDON ARCHITECTURE**
This shop system is designed as a **standalone addon/plugin** that can be installed on any Pterodactyl panel instance. The implementation must be:
- **Self-contained**: All code, migrations, and assets bundled together
- **Non-intrusive**: No modifications to core Pterodactyl files
- **Installable**: Simple installation process via Composer or manual upload
- **Configurable**: Panel owners can customize settings without code changes
- **Updatable**: Easy update mechanism without losing configuration
- **Removable**: Clean uninstallation process

## Overview
This document outlines a complete shop system addon for the Pterodactyl panel, designed as an installable package that other panel owners can deploy on their instances. The system follows existing architectural patterns while maintaining complete separation from core panel code.

## Core Principles
- **ADDON ARCHITECTURE**: Complete separation from core Pterodactyl code - no core file modifications
- **EASY INSTALLATION**: Simple installation process via Composer package or manual deployment
- **NO HARDCODED DATA**: All configurations, prices, and settings must be configurable through the admin panel or configuration files
- **FOLLOW EXISTING PATTERNS**: Use the same service/repository/controller structure as existing Pterodactyl modules
- **MAINTAIN CONSISTENCY**: Follow existing naming conventions, validation patterns, and code organization
- **SECURITY FIRST**: Implement proper authorization, validation, and audit logging for all financial operations
- **SCALABLE ARCHITECTURE**: Design for future extensibility and high-volume usage
- **VERSION COMPATIBILITY**: Support multiple Pterodactyl versions with compatibility layers
- **CLEAN UNINSTALL**: Complete removal capability without leaving traces in core system

## Addon Structure Overview

### Package Structure
```
pterodactyl-shop/
├── composer.json                 # Package definition and dependencies
├── README.md                    # Installation and configuration guide
├── LICENSE                      # License file
├── src/
│   ├── ShopServiceProvider.php  # Main service provider for Laravel
│   ├── Models/                  # Shop-specific models
│   ├── Services/                # Business logic services
│   ├── Repositories/            # Data access layer
│   ├── Http/
│   │   ├── Controllers/         # Shop controllers
│   │   ├── Requests/           # Form validation requests
│   │   └── Middleware/         # Shop-specific middleware
│   ├── Policies/               # Authorization policies
│   ├── Jobs/                   # Queue jobs
│   ├── Notifications/          # Email/Discord notifications
│   ├── PaymentGateways/        # Payment processor integrations
│   └── Transformers/           # API response transformers
├── database/
│   ├── migrations/             # Database schema
│   └── seeders/               # Default data seeders
├── resources/
│   ├── views/                 # Blade templates
│   ├── js/                    # Frontend JavaScript
│   ├── css/                   # Styling
│   └── lang/                  # Multi-language support
├── config/
│   └── shop.php              # Default configuration
├── routes/
│   ├── web.php               # Web routes
│   └── api.php               # API routes
└── tests/                    # Test suite
    ├── Unit/
    ├── Feature/
    └── TestCase.php
```

## 🎉 **IMPLEMENTATION STATUS - PHASE 3 MAJOR PROGRESS - BACKEND COMPLETE**

### ✅ **COMPLETED COMPONENTS**

#### Database Layer (100% Complete)
- ✅ **8 Migration Files** - Complete database schema
  - `shop_products` - Product catalog with sorting
  - `shop_plans` - Hosting plans with resource limits
  - `user_wallets` - User credit system
  - `wallet_transactions` - Transaction history
  - `shop_orders` - Order management with lifecycle
  - `shop_payments` - Payment processing records  
  - `shop_coupons` - Discount system
  - `shop_coupon_usage` - Usage tracking
- ✅ **Foreign Key Relationships** - Proper data integrity
- ✅ **Indexing Strategy** - Performance optimization
- ✅ **UUID Support** - External reference safety

#### Model Layer (100% Complete)
- ✅ **ShopProduct** - Product management with visibility
- ✅ **ShopPlan** - Plan configuration with resource limits
- ✅ **ShopOrder** - Order lifecycle management
- ✅ **ShopPayment** - Payment processing tracking
- ✅ **UserWallet** - Wallet operations with balance management
- ✅ **WalletTransaction** - Transaction history with metadata
- ✅ **ShopCoupon** - Coupon system with validation
- ✅ **ShopCouponUsage** - Usage tracking and limits
- ✅ **Relationships** - Proper Eloquent relationships
- ✅ **Validation Rules** - Comprehensive data validation
- ✅ **Business Logic** - Status checks, calculations, formatting

#### Repository Layer (75% Complete)
- ✅ **ShopProductRepository** - Product data access with visibility
- ✅ **ShopPlanRepository** - Plan queries with node/location filtering
- ✅ **ShopOrderRepository** - Order management with statistics
- 🔄 **Missing**: UserWalletRepository, PaymentRepository, CouponRepository

#### Service Layer (50% Complete)
- ✅ **ShopOrderService** - Complete order lifecycle
  - Order creation with coupon support
  - Server provisioning integration
  - Renewal processing
  - Suspend/unsuspend/terminate operations
- ✅ **WalletService** - Comprehensive wallet operations
  - Credit/debit operations with transaction safety
  - Transfer functionality
  - Transaction history and statistics
- 🔄 **Missing**: PaymentGatewayService, BillingService, NotificationService

#### Configuration System (100% Complete)
- ✅ **ShopServiceProvider** - Laravel integration with auto-discovery
- ✅ **shop.php Config** - Comprehensive settings with environment variables
- ✅ **Composer Package** - PSR-4 autoloading and dependencies

### 🚀 **NEXT PRIORITY COMPONENTS**

#### Immediate Next Steps (Phase 2)
1. **Complete Repository Layer** - UserWallet, Payment, Coupon repositories
2. **Payment Gateway Integration** - Stripe and PayPal services
3. **Frontend Controllers** - Web interface for shop functionality
4. **Background Jobs** - Automated billing and renewal processing
5. **Admin Panel Integration** - Management interface

## Phase 0: Addon Foundation & Package Setup

### 0.1 Composer Package Definition

#### composer.json
```json
{
    "name": "pterodactyl-addons/shop-system",
    "description": "Complete shop and billing system addon for Pterodactyl Panel",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Your Organization",
            "email": "contact@yourorg.com"
        }
    ],
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0",
        "stripe/stripe-php": "^10.0",
        "paypal/paypal-checkout-sdk": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "orchestra/testbench": "^8.0"
    },
    "autoload": {
        "psr-4": {
            "PterodactylAddons\\ShopSystem\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "PterodactylAddons\\ShopSystem\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "PterodactylAddons\\ShopSystem\\ShopServiceProvider"
            ]
        },
        "pterodactyl": {
            "minimum_version": "1.11.0",
            "maximum_version": "1.99.99"
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

### 0.2 Service Provider Implementation

#### src/ShopServiceProvider.php
```php
<?php

namespace PterodactylAddons\ShopSystem;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Console\Scheduling\Schedule;
use PterodactylAddons\ShopSystem\Models\Shop\ShopProduct;
use PterodactylAddons\ShopSystem\Policies\ShopProductPolicy;

class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/shop.php',
            'shop'
        );

        // Register services
        $this->registerServices();
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'shop');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'shop');

        // Register policies
        $this->registerPolicies();

        // Schedule tasks
        $this->scheduleShopTasks();

        // Publish assets for customization
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/shop.php' => config_path('shop.php'),
            ], 'shop-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/shop'),
            ], 'shop-views');

            $this->publishes([
                __DIR__ . '/../resources/js' => public_path('vendor/shop/js'),
                __DIR__ . '/../resources/css' => public_path('vendor/shop/css'),
            ], 'shop-assets');
        }

        // Add shop navigation to admin panel
        $this->extendAdminNavigation();
    }

    private function registerServices(): void
    {
        // Bind repositories
        $this->app->bind(
            \PterodactylAddons\ShopSystem\Contracts\ShopProductRepositoryInterface::class,
            \PterodactylAddons\ShopSystem\Repositories\Eloquent\ShopProductRepository::class
        );

        // Bind services
        $this->app->singleton(
            \PterodactylAddons\ShopSystem\Services\ShopOrderService::class
        );
        
        $this->app->singleton(
            \PterodactylAddons\ShopSystem\Services\WalletService::class
        );
    }

    private function registerPolicies(): void
    {
        Gate::policy(ShopProduct::class, ShopProductPolicy::class);
        // Register other policies...
    }

    private function scheduleShopTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Process renewals every hour
            $schedule->call(function () {
                app(\PterodactylAddons\ShopSystem\Services\BillingService::class)
                    ->processRenewals();
            })->hourly()->name('shop:process-renewals');

            // Process grace periods every 6 hours
            $schedule->call(function () {
                app(\PterodactylAddons\ShopSystem\Services\BillingService::class)
                    ->processGracePeriodExpirations();
            })->everySixHours()->name('shop:process-grace-periods');
        });
    }

    private function extendAdminNavigation(): void
    {
        // Hook into Pterodactyl's admin navigation
        // This would be implemented based on how Pterodactyl handles admin nav
        if (class_exists('\Pterodactyl\Http\ViewComposers\AdminNavigationComposer')) {
            // Add shop navigation items
        }
    }
}
```

### 0.3 Installation Script

#### install.php
```php
<?php

/**
 * Pterodactyl Shop System Installation Script
 * 
 * This script handles the installation of the shop system addon
 */

class ShopInstaller
{
    private $pterodactylPath;
    private $errors = [];

    public function __construct()
    {
        $this->pterodactylPath = getcwd();
    }

    public function install(): bool
    {
        echo "🚀 Installing Pterodactyl Shop System...\n\n";

        // Check prerequisites
        if (!$this->checkPrerequisites()) {
            $this->displayErrors();
            return false;
        }

        // Install via Composer if possible
        if ($this->hasComposer()) {
            return $this->installViaComposer();
        }

        // Manual installation
        return $this->manualInstall();
    }

    private function checkPrerequisites(): bool
    {
        // Check if this is a Pterodactyl installation
        if (!file_exists($this->pterodactylPath . '/app/Models/Server.php')) {
            $this->errors[] = 'This does not appear to be a Pterodactyl installation';
            return false;
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $this->errors[] = 'PHP 8.1+ is required';
            return false;
        }

        // Check Laravel version
        $composer = json_decode(file_get_contents($this->pterodactylPath . '/composer.json'), true);
        // Additional version checks...

        return empty($this->errors);
    }

    private function installViaComposer(): bool
    {
        echo "📦 Installing via Composer...\n";
        
        exec('composer require pterodactyl-addons/shop-system', $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->runPostInstallSteps();
            return true;
        }
        
        return false;
    }

    private function runPostInstallSteps(): void
    {
        echo "🔄 Running post-installation steps...\n";
        
        // Run migrations
        exec('php artisan migrate --path=/vendor/pterodactyl-addons/shop-system/database/migrations');
        
        // Publish configuration
        exec('php artisan vendor:publish --tag=shop-config');
        
        // Clear caches
        exec('php artisan config:clear');
        exec('php artisan route:clear');
        exec('php artisan view:clear');
        
        echo "✅ Shop system installed successfully!\n";
        echo "🔧 Please review and update config/shop.php\n";
        echo "🎉 Visit /admin/shop to get started!\n";
    }
}

// Run installer if called directly
if (php_sapi_name() === 'cli') {
    $installer = new ShopInstaller();
    exit($installer->install() ? 0 : 1);
}
```

### 0.4 Compatibility Layer

#### src/Compatibility/PterodactylVersionManager.php
```php
<?php

namespace PterodactylAddons\ShopSystem\Compatibility;

class PterodactylVersionManager
{
    private string $version;

    public function __construct()
    {
        $this->version = $this->detectPterodactylVersion();
    }

    public function detectPterodactylVersion(): string
    {
        // Read from composer.lock or app version
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);
        
        foreach ($composerLock['packages'] as $package) {
            if ($package['name'] === 'pterodactyl/panel') {
                return $package['version'];
            }
        }
        
        return '1.11.0'; // Default fallback
    }

    public function isCompatible(): bool
    {
        return version_compare($this->version, '1.11.0', '>=') 
            && version_compare($this->version, '2.0.0', '<');
    }

    public function getServerCreationService(): string
    {
        // Return appropriate service class based on version
        if (version_compare($this->version, '1.11.0', '>=')) {
            return \Pterodactyl\Services\Servers\ServerCreationService::class;
        }
        
        throw new \Exception('Unsupported Pterodactyl version: ' . $this->version);
    }

    public function getUserModel(): string
    {
        return \Pterodactyl\Models\User::class;
    }

    public function getServerModel(): string
    {
        return \Pterodactyl\Models\Server::class;
    }
}
```

## Phase 1: Core Infrastructure & Database Schema

### 1.1 Database Migrations (Required Tables)

#### Shop Products
```sql
CREATE TABLE shop_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('server', 'addon', 'resource') NOT NULL DEFAULT 'server',
    status ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
    sort_order INTEGER DEFAULT 0,
    metadata JSON, -- Stores flexible product configuration
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_type_status (type, status),
    INDEX idx_sort_order (sort_order)
);
```

#### Shop Plans  
```sql
CREATE TABLE shop_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Server Resources (following existing server table structure)
    memory INTEGER NOT NULL DEFAULT 0,
    swap INTEGER NOT NULL DEFAULT 0,
    disk INTEGER NOT NULL DEFAULT 0,
    io INTEGER NOT NULL DEFAULT 500,
    cpu INTEGER NOT NULL DEFAULT 0,
    threads VARCHAR(255) NULL,
    allocation_limit INTEGER NULL,
    database_limit INTEGER NULL,
    backup_limit INTEGER NOT NULL DEFAULT 0,
    
    -- Billing Configuration
    price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_hourly DECIMAL(10,4) NOT NULL DEFAULT 0,
    setup_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    
    -- Availability & Limits
    stock_limit INTEGER NULL, -- NULL = unlimited
    max_per_user INTEGER NULL, -- NULL = unlimited
    
    -- Node/Location restrictions
    allowed_locations JSON NULL, -- Array of location IDs
    allowed_nodes JSON NULL, -- Array of node IDs
    
    status ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES shop_products(id) ON DELETE CASCADE,
    INDEX idx_product_status (product_id, status),
    INDEX idx_pricing (price_monthly, price_hourly)
);
```

#### User Wallets & Balance
```sql
CREATE TABLE user_wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INTEGER UNSIGNED NOT NULL,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_currency (user_id, currency)
);
```

#### Wallet Transactions
```sql
CREATE TABLE wallet_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    wallet_id BIGINT UNSIGNED NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    balance_before DECIMAL(12,2) NOT NULL,
    balance_after DECIMAL(12,2) NOT NULL,
    description VARCHAR(500) NOT NULL,
    metadata JSON NULL, -- Reference to orders, payments, etc.
    created_at TIMESTAMP NULL,
    
    FOREIGN KEY (wallet_id) REFERENCES user_wallets(id) ON DELETE CASCADE,
    INDEX idx_wallet_created (wallet_id, created_at),
    INDEX idx_type_created (type, created_at)
);
```

#### Shop Orders
```sql
CREATE TABLE shop_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    user_id INTEGER UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    server_id INTEGER UNSIGNED NULL, -- Linked after provisioning
    
    -- Order Details
    status ENUM('pending', 'processing', 'active', 'suspended', 'cancelled', 'terminated') NOT NULL DEFAULT 'pending',
    billing_cycle ENUM('hourly', 'monthly', 'quarterly', 'semi_annually', 'annually', 'one_time') NOT NULL,
    
    -- Pricing (stored to maintain history)
    amount DECIMAL(10,2) NOT NULL,
    setup_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    
    -- Billing Dates
    next_due_at TIMESTAMP NULL,
    last_renewed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    suspended_at TIMESTAMP NULL,
    terminated_at TIMESTAMP NULL,
    
    -- Configuration snapshot (for consistency)
    server_config JSON NOT NULL, -- Memory, CPU, etc at order time
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES shop_plans(id),
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    
    INDEX idx_user_status (user_id, status),
    INDEX idx_billing_due (billing_cycle, next_due_at),
    INDEX idx_status_dates (status, next_due_at, expires_at)
);
```

#### Payment Gateways Configuration
```sql
CREATE TABLE payment_gateways (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    driver VARCHAR(50) NOT NULL, -- stripe, paypal, etc.
    enabled BOOLEAN NOT NULL DEFAULT false,
    sort_order INTEGER DEFAULT 0,
    configuration JSON NOT NULL, -- Gateway-specific settings
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_enabled_order (enabled, sort_order)
);
```

#### Payment Records
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    user_id INTEGER UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    gateway_id BIGINT UNSIGNED NOT NULL,
    
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    
    -- Gateway specifics
    gateway_transaction_id VARCHAR(255) NULL,
    gateway_fee DECIMAL(10,2) NULL,
    gateway_response JSON NULL,
    
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES shop_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id),
    
    INDEX idx_user_status (user_id, status),
    INDEX idx_gateway_transaction (gateway_id, gateway_transaction_id),
    INDEX idx_order_payment (order_id)
);
```

### 1.2 Model Classes

Following Pterodactyl's model structure with proper relationships, validation, and activity logging:

#### ShopProduct Model
```php
namespace PterodactylAddons\ShopSystem\Models\Shop;

use Pterodactyl\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopProduct extends Model
{
    protected $table = 'shop_products';
    
    protected $fillable = [
        'name', 'description', 'type', 'status', 'sort_order', 'metadata'
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];
    
    public static array $validationRules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'type' => 'required|in:server,addon,resource',
        'status' => 'required|in:active,inactive,archived',
        'sort_order' => 'integer|min:0',
    ];
    
    public function plans(): HasMany
    {
        return $this->hasMany(ShopPlan::class, 'product_id');
    }
    
    public function activePlans(): HasMany  
    {
        return $this->plans()->where('status', 'active');
    }
}
```

#### ShopPlan Model
```php
namespace PterodactylAddons\ShopSystem\Models\Shop;

use Pterodactyl\Models\Model;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopPlan extends Model
{
    protected $table = 'shop_plans';
    
    protected $fillable = [
        'product_id', 'name', 'description', 'memory', 'swap', 'disk', 'io', 
        'cpu', 'threads', 'allocation_limit', 'database_limit', 'backup_limit',
        'price_monthly', 'price_hourly', 'setup_fee', 'stock_limit', 'max_per_user',
        'allowed_locations', 'allowed_nodes', 'status', 'sort_order'
    ];
    
    protected $casts = [
        'memory' => 'integer',
        'swap' => 'integer', 
        'disk' => 'integer',
        'io' => 'integer',
        'cpu' => 'integer',
        'allocation_limit' => 'integer',
        'database_limit' => 'integer',
        'backup_limit' => 'integer',
        'price_monthly' => 'decimal:2',
        'price_hourly' => 'decimal:4',
        'setup_fee' => 'decimal:2',
        'stock_limit' => 'integer',
        'max_per_user' => 'integer',
        'allowed_locations' => 'array',
        'allowed_nodes' => 'array',
        'sort_order' => 'integer',
    ];
    
    public static array $validationRules = [
        'product_id' => 'required|exists:shop_products,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'memory' => 'required|integer|min:0',
        'disk' => 'required|integer|min:0',
        'cpu' => 'required|integer|min:0',
        'price_monthly' => 'required|numeric|min:0',
        'price_hourly' => 'required|numeric|min:0',
    ];
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'product_id');
    }
    
    public function orders(): HasMany
    {
        return $this->hasMany(ShopOrder::class, 'plan_id');
    }
    
    public function getAvailableLocations()
    {
        if (empty($this->allowed_locations)) {
            return Location::all();
        }
        
        return Location::whereIn('id', $this->allowed_locations)->get();
    }
    
    public function getAvailableNodes()
    {
        if (empty($this->allowed_nodes)) {
            return Node::all();
        }
        
        return Node::whereIn('id', $this->allowed_nodes)->get();
    }
}
```

### 1.3 Repository Pattern Implementation

Following existing Pterodactyl repository structure with addon namespacing:

#### src/Contracts/ShopProductRepositoryInterface.php
```php
namespace PterodactylAddons\ShopSystem\Contracts;

use Illuminate\Support\Collection;
use PterodactylAddons\ShopSystem\Models\Shop\ShopProduct;

interface ShopProductRepositoryInterface
{
    public function getActiveProducts(): Collection;
    public function getProductWithPlans(string $uuid): ShopProduct;
    public function search(array $filters);
}
```

#### ShopProductRepository
```php
namespace PterodactylAddons\ShopSystem\Repositories\Eloquent;

use PterodactylAddons\ShopSystem\Models\Shop\ShopProduct;
use PterodactylAddons\ShopSystem\Contracts\ShopProductRepositoryInterface;
use Illuminate\Support\Collection;

class ShopProductRepository implements ShopProductRepositoryInterface
{
    public function __construct(
        private ShopProduct $model
    ) {}
    
    public function getActiveProducts(): Collection
    {
        return $this->model->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
    
    public function getProductWithPlans(string $uuid): ShopProduct
    {
        return $this->model->with(['activePlans' => function ($query) {
                $query->orderBy('sort_order')->orderBy('price_monthly');
            }])
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
    
    public function search(array $filters)
    {
        $query = $this->model->newQuery();
        
        if (!empty($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
```

## Phase 2: Service Layer Implementation

### 2.1 Core Services

Following Pterodactyl's service architecture with dependency injection and proper error handling:

#### ShopOrderService
```php
namespace Pterodactyl\Services\Shop;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Shop\ShopOrder;
use Pterodactyl\Models\Shop\ShopPlan;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Repositories\Eloquent\Shop\ShopOrderRepository;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;

class ShopOrderService
{
    public function __construct(
        private ConnectionInterface $connection,
        private ServerCreationService $serverCreationService,
        private WalletService $walletService,
        private ShopOrderRepository $repository,
        private PaymentProcessingService $paymentService
    ) {}
    
    public function createOrder(User $user, ShopPlan $plan, array $config): ShopOrder
    {
        return $this->connection->transaction(function () use ($user, $plan, $config) {
            // Validate user can purchase
            $this->validatePurchase($user, $plan);
            
            // Create order record
            $order = $this->repository->create([
                'uuid' => Uuid::uuid4()->toString(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $config['billing_cycle'],
                'amount' => $this->calculatePrice($plan, $config['billing_cycle']),
                'setup_fee' => $plan->setup_fee,
                'server_config' => $this->buildServerConfig($plan, $config),
                'next_due_at' => $this->calculateNextDue($config['billing_cycle']),
            ]);
            
            // Process payment
            $this->processPayment($order, $config['payment_method']);
            
            return $order;
        });
    }
    
    private function validatePurchase(User $user, ShopPlan $plan): void
    {
        if ($plan->status !== 'active') {
            throw new DisplayException('This plan is no longer available.');
        }
        
        if ($plan->max_per_user && $user->orders()->where('plan_id', $plan->id)->where('status', 'active')->count() >= $plan->max_per_user) {
            throw new DisplayException('You have reached the maximum number of this plan type.');
        }
        
        if ($plan->stock_limit && $plan->orders()->where('status', 'active')->count() >= $plan->stock_limit) {
            throw new DisplayException('This plan is currently out of stock.');
        }
    }
    
    private function calculatePrice(ShopPlan $plan, string $billingCycle): float
    {
        return match($billingCycle) {
            'hourly' => $plan->price_hourly,
            'monthly' => $plan->price_monthly,
            'quarterly' => $plan->price_monthly * 3,
            'semi_annually' => $plan->price_monthly * 6,
            'annually' => $plan->price_monthly * 12,
            default => throw new DisplayException('Invalid billing cycle specified.')
        };
    }
    
    private function buildServerConfig(ShopPlan $plan, array $config): array
    {
        return [
            'memory' => $plan->memory,
            'swap' => $plan->swap,
            'disk' => $plan->disk,
            'io' => $plan->io,
            'cpu' => $plan->cpu,
            'threads' => $plan->threads,
            'allocation_limit' => $plan->allocation_limit,
            'database_limit' => $plan->database_limit,
            'backup_limit' => $plan->backup_limit,
            'node_id' => $config['node_id'] ?? null,
            'egg_id' => $config['egg_id'] ?? null,
        ];
    }
    
    public function provisionServer(ShopOrder $order): void
    {
        $serverConfig = array_merge($order->server_config, [
            'name' => $this->generateServerName($order),
            'owner_id' => $order->user_id,
        ]);
        
        $server = $this->serverCreationService->create($order->user, $serverConfig);
        
        $order->update([
            'server_id' => $server->id,
            'status' => 'active',
        ]);
    }
    
    private function generateServerName(ShopOrder $order): string
    {
        return sprintf('%s-%s', $order->plan->name, substr($order->uuid, 0, 8));
    }
}
```

#### WalletService
```php
namespace Pterodactyl\Services\Shop;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Shop\UserWallet;
use Pterodactyl\Models\Shop\WalletTransaction;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;

class WalletService
{
    public function __construct(
        private ConnectionInterface $connection
    ) {}
    
    public function getWallet(User $user, string $currency = 'USD'): UserWallet
    {
        return UserWallet::firstOrCreate([
            'user_id' => $user->id,
            'currency' => $currency,
        ], [
            'balance' => 0.00,
        ]);
    }
    
    public function getBalance(User $user, string $currency = 'USD'): float
    {
        return $this->getWallet($user, $currency)->balance;
    }
    
    public function hasSufficientBalance(User $user, float $amount, string $currency = 'USD'): bool
    {
        return $this->getBalance($user, $currency) >= $amount;
    }
    
    public function credit(User $user, float $amount, string $description, array $metadata = []): WalletTransaction
    {
        if ($amount <= 0) {
            throw new DisplayException('Credit amount must be greater than zero.');
        }
        
        return $this->connection->transaction(function () use ($user, $amount, $description, $metadata) {
            $wallet = $this->getWallet($user);
            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;
            
            $wallet->update(['balance' => $balanceAfter]);
            
            return WalletTransaction::create([
                'uuid' => Uuid::uuid4()->toString(),
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }
    
    public function debit(User $user, float $amount, string $description, array $metadata = []): WalletTransaction
    {
        if ($amount <= 0) {
            throw new DisplayException('Debit amount must be greater than zero.');
        }
        
        return $this->connection->transaction(function () use ($user, $amount, $description, $metadata) {
            $wallet = $this->getWallet($user);
            
            if ($wallet->balance < $amount) {
                throw new DisplayException('Insufficient wallet balance.');
            }
            
            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;
            
            $wallet->update(['balance' => $balanceAfter]);
            
            return WalletTransaction::create([
                'uuid' => Uuid::uuid4()->toString(),
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }
}
```

### 2.2 Billing & Automation Services

#### BillingService
```php
namespace Pterodactyl\Services\Shop;

use Pterodactyl\Models\Shop\ShopOrder;
use Pterodactyl\Jobs\Shop\ProcessRenewalJob;
use Pterodactyl\Jobs\Shop\SuspendOrderJob;
use Carbon\Carbon;

class BillingService  
{
    public function __construct(
        private WalletService $walletService,
        private PaymentProcessingService $paymentService,
        private ShopOrderService $orderService
    ) {}
    
    public function processRenewals(): void
    {
        $dueOrders = ShopOrder::where('status', 'active')
            ->where('next_due_at', '<=', now())
            ->get();
            
        foreach ($dueOrders as $order) {
            ProcessRenewalJob::dispatch($order);
        }
    }
    
    public function renewOrder(ShopOrder $order): void
    {
        try {
            // Attempt wallet payment first
            if ($this->walletService->hasSufficientBalance($order->user, $order->amount)) {
                $this->walletService->debit(
                    $order->user, 
                    $order->amount, 
                    "Renewal for order #{$order->uuid}"
                );
                $this->completeRenewal($order);
                return;
            }
            
            // Try saved payment methods
            $this->processPaymentMethodRenewal($order);
            
        } catch (Exception $e) {
            $this->handleRenewalFailure($order, $e);
        }
    }
    
    private function completeRenewal(ShopOrder $order): void
    {
        $order->update([
            'last_renewed_at' => now(),
            'next_due_at' => $this->calculateNextDue($order->billing_cycle),
        ]);
        
        Activity::event('shop:order.renewed')
            ->subject($order->server)
            ->actor($order->user)
            ->property(['order_uuid' => $order->uuid, 'amount' => $order->amount])
            ->log();
    }
    
    private function handleRenewalFailure(ShopOrder $order, Exception $exception): void
    {
        $gracePeriodHours = config('shop.billing.grace_period_hours', 72);
        
        $order->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'expires_at' => now()->addHours($gracePeriodHours),
        ]);
        
        // Schedule suspension job
        SuspendOrderJob::dispatch($order)->delay(now()->addHours($gracePeriodHours));
        
        // Send notification to user
        $this->sendRenewalFailureNotification($order, $exception);
    }
    
    public function processGracePeriodExpirations(): void
    {
        $expiredOrders = ShopOrder::where('status', 'suspended')
            ->where('expires_at', '<=', now())
            ->get();
            
        foreach ($expiredOrders as $order) {
            $this->terminateOrder($order);
        }
    }
    
    private function terminateOrder(ShopOrder $order): void
    {
        if ($order->server) {
            $order->server->update(['status' => 'suspended']);
        }
        
        $order->update([
            'status' => 'terminated',
            'terminated_at' => now(),
        ]);
    }
}
```

## Phase 3: Admin Interface Integration

### 3.1 Admin Controllers

Following existing admin controller patterns with proper middleware and authorization:

#### Admin\Shop\ProductsController
```php
namespace Pterodactyl\Http\Controllers\Admin\Shop;

use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Shop\ShopProductService;
use Pterodactyl\Repositories\Eloquent\Shop\ShopProductRepository;
use Pterodactyl\Http\Requests\Admin\Shop\StoreProductRequest;
use Pterodactyl\Http\Requests\Admin\Shop\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;

class ProductsController extends Controller
{
    public function __construct(
        private ShopProductService $productService,
        private ShopProductRepository $repository,
        private AlertsMessageBag $alert
    ) {}
    
    public function index(Request $request): View
    {
        $products = $this->repository->search($request->get('filter', []))
            ->paginate(25);
            
        return view('admin.shop.products.index', compact('products'));
    }
    
    public function create(): View
    {
        return view('admin.shop.products.create');
    }
    
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->productService->create($request->validated());
        
        $this->alert->success('Product created successfully.')->flash();
        
        return redirect()->route('admin.shop.products.view', $product->uuid);
    }
    
    public function view(string $uuid): View
    {
        $product = $this->repository->getProductWithPlans($uuid);
        
        return view('admin.shop.products.view', compact('product'));
    }
    
    public function edit(string $uuid): View
    {
        $product = $this->repository->findByUuid($uuid);
        
        return view('admin.shop.products.edit', compact('product'));
    }
    
    public function update(UpdateProductRequest $request, string $uuid): RedirectResponse
    {
        $product = $this->repository->findByUuid($uuid);
        $this->productService->update($product, $request->validated());
        
        $this->alert->success('Product updated successfully.')->flash();
        
        return redirect()->route('admin.shop.products.view', $product->uuid);
    }
    
    public function delete(string $uuid): RedirectResponse
    {
        $product = $this->repository->findByUuid($uuid);
        $this->productService->delete($product);
        
        $this->alert->success('Product deleted successfully.')->flash();
        
        return redirect()->route('admin.shop.products');
    }
}
```

### 3.2 Form Requests

Following Pterodactyl's validation patterns:

#### StoreProductRequest
```php
namespace Pterodactyl\Http\Requests\Admin\Shop;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class StoreProductRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:server,addon,resource',
            'status' => 'required|in:active,inactive,archived',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array',
        ];
    }
    
    public function attributes(): array
    {
        return [
            'name' => 'Product Name',
            'type' => 'Product Type',
            'sort_order' => 'Sort Order',
        ];
    }
}
```

#### StorePlanRequest
```php
namespace Pterodactyl\Http\Requests\Admin\Shop;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class StorePlanRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:shop_products,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'memory' => 'required|integer|min:0',
            'swap' => 'integer|min:-1',
            'disk' => 'required|integer|min:0',
            'io' => 'integer|between:10,1000',
            'cpu' => 'required|integer|min:0',
            'threads' => 'nullable|string',
            'allocation_limit' => 'nullable|integer|min:0',
            'database_limit' => 'nullable|integer|min:0',
            'backup_limit' => 'integer|min:0',
            'price_monthly' => 'required|numeric|min:0',
            'price_hourly' => 'required|numeric|min:0',
            'setup_fee' => 'numeric|min:0',
            'stock_limit' => 'nullable|integer|min:1',
            'max_per_user' => 'nullable|integer|min:1',
            'allowed_locations' => 'nullable|array',
            'allowed_locations.*' => 'integer|exists:locations,id',
            'allowed_nodes' => 'nullable|array',
            'allowed_nodes.*' => 'integer|exists:nodes,id',
            'status' => 'required|in:active,inactive,archived',
        ];
    }
}
```

## Phase 4: User Interface & Frontend

### 4.1 User Controllers

#### Shop\ShopController (Client Area)
```php
namespace Pterodactyl\Http\Controllers\Base\Shop;

use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\Shop\ShopProductRepository;
use Pterodactyl\Services\Shop\WalletService;
use Pterodactyl\Services\Shop\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    public function __construct(
        private ShopProductRepository $productRepository,
        private WalletService $walletService,
        private PaymentGatewayService $paymentGatewayService
    ) {}
    
    public function index(): View
    {
        $products = $this->productRepository->getActiveProductsWithPlans();
        
        return view('base.shop.index', compact('products'));
    }
    
    public function product(string $uuid): View
    {
        $product = $this->productRepository->getProductWithPlans($uuid);
        
        return view('base.shop.product', compact('product'));
    }
    
    public function checkout(string $planUuid): View
    {
        $plan = ShopPlan::where('uuid', $planUuid)->firstOrFail();
        
        $this->authorize('purchase', $plan);
        
        $user = Auth::user();
        $walletBalance = $this->walletService->getBalance($user);
        $paymentMethods = $this->paymentGatewayService->getAvailableGateways();
        $availableNodes = $plan->getAvailableNodes();
        
        return view('base.shop.checkout', compact(
            'plan', 'walletBalance', 'paymentMethods', 'availableNodes'
        ));
    }
}
```

### 4.2 API Endpoints

Following existing API structure with proper transformers:

#### Api\Client\ShopController
```php
namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Transformers\Api\Client\Shop\ShopProductTransformer;
use Pterodactyl\Repositories\Eloquent\Shop\ShopProductRepository;
use Illuminate\Http\Request;

class ShopController extends ClientApiController
{
    public function __construct(
        private ShopProductRepository $productRepository
    ) {
        parent::__construct();
    }
    
    public function index(Request $request): array
    {
        $products = $this->productRepository->getActiveProducts();
        
        return $this->fractal->collection($products)
            ->transformWith($this->getTransformer(ShopProductTransformer::class))
            ->toArray();
    }
    
    public function show(string $uuid): array
    {
        $product = $this->productRepository->getProductWithPlans($uuid);
        
        return $this->fractal->item($product)
            ->transformWith($this->getTransformer(ShopProductTransformer::class))
            ->parseIncludes(['plans'])
            ->toArray();
    }
}
```

## Phase 5: Configuration & Settings Integration

### 5.1 Configuration Files

#### config/shop.php
```php
<?php

return [
    'enabled' => env('SHOP_ENABLED', false),
    
    'currency' => [
        'default' => env('SHOP_CURRENCY', 'USD'),
        'symbol' => env('SHOP_CURRENCY_SYMBOL', '$'),
        'precision' => env('SHOP_CURRENCY_PRECISION', 2),
    ],
    
    'billing' => [
        'grace_period_hours' => env('SHOP_GRACE_PERIOD', 72),
        'renewal_reminder_days' => explode(',', env('SHOP_RENEWAL_REMINDER', '7,3,1')),
        'auto_suspend_after_grace' => env('SHOP_AUTO_SUSPEND', true),
        'auto_terminate_days' => env('SHOP_AUTO_TERMINATE', 14),
    ],
    
    'wallet' => [
        'enabled' => env('SHOP_WALLET_ENABLED', true),
        'minimum_deposit' => env('SHOP_MINIMUM_DEPOSIT', 5.00),
        'maximum_balance' => env('SHOP_MAXIMUM_BALANCE', 10000.00),
    ],
    
    'payment_gateways' => [
        'stripe' => [
            'driver' => Pterodactyl\PaymentGateways\StripeGateway::class,
            'name' => 'Credit Card (Stripe)',
        ],
        'paypal' => [
            'driver' => Pterodactyl\PaymentGateways\PayPalGateway::class,
            'name' => 'PayPal',
        ],
    ],
    
    'limits' => [
        'max_orders_per_user' => env('SHOP_MAX_ORDERS_PER_USER', null),
        'max_pending_orders' => env('SHOP_MAX_PENDING_ORDERS', 3),
    ],
];
```

### 5.2 Settings Integration

Extending existing settings system in the database:

```php
// Add to existing settings seeder or create new migration
$shopSettings = [
    'shop:enabled' => 'false',
    'shop:currency' => 'USD',
    'shop:tax_rate' => '0.00',
    'shop:grace_period_hours' => '72',
    'shop:wallet_enabled' => 'true',
    'shop:minimum_deposit' => '5.00',
    'shop:maintenance_mode' => 'false',
    'shop:maintenance_message' => 'The shop is currently under maintenance.',
];

foreach ($shopSettings as $key => $value) {
    Setting::updateOrCreate(['key' => $key], ['value' => $value]);
}
```

## Phase 6: Security & Audit Implementation

### 6.1 Activity Logging Integration

Following existing activity logging patterns:

#### ShopActivityLogger
```php
namespace Pterodactyl\Services\Shop;

use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Shop\ShopOrder;
use Pterodactyl\Models\Shop\Payment;

class ShopActivityLogger
{
    public function logPurchase(ShopOrder $order): void
    {
        Activity::event('shop:order.created')
            ->subject($order->server)
            ->actor($order->user)
            ->property([
                'order_uuid' => $order->uuid,
                'plan_name' => $order->plan->name,
                'amount' => $order->amount,
                'billing_cycle' => $order->billing_cycle,
            ])
            ->log();
    }
    
    public function logPayment(Payment $payment): void
    {
        Activity::event('shop:payment.completed')
            ->actor($payment->user)
            ->property([
                'payment_uuid' => $payment->uuid,
                'amount' => $payment->amount,
                'gateway' => $payment->gateway->name,
                'transaction_id' => $payment->gateway_transaction_id,
            ])
            ->log();
    }
    
    public function logWalletTransaction(WalletTransaction $transaction): void
    {
        Activity::event('shop:wallet.' . $transaction->type)
            ->actor($transaction->wallet->user)
            ->property([
                'transaction_uuid' => $transaction->uuid,
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'description' => $transaction->description,
            ])
            ->log();
    }
    
    public function logOrderStatusChange(ShopOrder $order, string $oldStatus, string $newStatus): void
    {
        Activity::event('shop:order.status_changed')
            ->subject($order->server)
            ->actor($order->user)
            ->property([
                'order_uuid' => $order->uuid,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ])
            ->log();
    }
}
```

### 6.2 Authorization Policies

#### ShopOrderPolicy
```php
namespace Pterodactyl\Policies\Shop;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Shop\ShopOrder;
use Pterodactyl\Models\Shop\ShopPlan;

class ShopOrderPolicy
{
    public function view(User $user, ShopOrder $order): bool
    {
        return $user->id === $order->user_id || $user->root_admin;
    }
    
    public function purchase(User $user, ShopPlan $plan): bool
    {
        // Check if plan is available
        if ($plan->status !== 'active') {
            return false;
        }
        
        // Check user-specific limits
        if ($plan->max_per_user) {
            $userOrderCount = ShopOrder::where('user_id', $user->id)
                ->where('plan_id', $plan->id)
                ->whereIn('status', ['active', 'suspended'])
                ->count();
                
            if ($userOrderCount >= $plan->max_per_user) {
                return false;
            }
        }
        
        // Check global shop settings
        $maxOrdersPerUser = config('shop.limits.max_orders_per_user');
        if ($maxOrdersPerUser) {
            $totalUserOrders = ShopOrder::where('user_id', $user->id)
                ->whereIn('status', ['active', 'suspended'])
                ->count();
                
            if ($totalUserOrders >= $maxOrdersPerUser) {
                return false;
            }
        }
        
        return true;
    }
    
    public function cancel(User $user, ShopOrder $order): bool
    {
        return ($user->id === $order->user_id || $user->root_admin) 
            && in_array($order->status, ['active', 'suspended']);
    }
    
    public function renew(User $user, ShopOrder $order): bool
    {
        return $user->id === $order->user_id 
            && in_array($order->status, ['active', 'suspended']);
    }
}
```

## Phase 7: Queue Jobs & Automation

### 7.1 Queue Jobs

Following existing job patterns:

#### ProcessShopOrderJob
```php
namespace Pterodactyl\Jobs\Shop;

use Pterodactyl\Jobs\Job;
use Pterodactyl\Models\Shop\ShopOrder;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Shop\ShopOrderService;
use Pterodactyl\Services\Shop\ShopActivityLogger;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessShopOrderJob extends Job implements ShouldQueue
{
    use SerializesModels;
    
    public function __construct(private ShopOrder $order) {}
    
    public function handle(
        ServerCreationService $serverCreationService,
        ShopOrderService $orderService,
        ShopActivityLogger $activityLogger
    ): void {
        try {
            $server = $serverCreationService->create($this->order->user, [
                'name' => $this->generateServerName($this->order),
                'memory' => $this->order->server_config['memory'],
                'disk' => $this->order->server_config['disk'],
                'cpu' => $this->order->server_config['cpu'],
                'swap' => $this->order->server_config['swap'],
                'io' => $this->order->server_config['io'],
                'allocation_limit' => $this->order->server_config['allocation_limit'],
                'database_limit' => $this->order->server_config['database_limit'],
                'backup_limit' => $this->order->server_config['backup_limit'],
                'node_id' => $this->order->server_config['node_id'],
                'egg_id' => $this->order->server_config['egg_id'],
            ]);
            
            $orderService->linkServerToOrder($this->order, $server);
            $orderService->activateOrder($this->order);
            
            $activityLogger->logPurchase($this->order);
            
        } catch (Exception $e) {
            $orderService->failOrder($this->order, $e->getMessage());
            throw $e;
        }
    }
    
    private function generateServerName(ShopOrder $order): string
    {
        $baseName = str_slug($order->plan->name);
        $suffix = substr($order->uuid, 0, 8);
        
        return "{$baseName}-{$suffix}";
    }
}
```

#### ProcessRenewalJob
```php
namespace Pterodactyl\Jobs\Shop;

use Pterodactyl\Jobs\Job;
use Pterodactyl\Models\Shop\ShopOrder;
use Pterodactyl\Services\Shop\BillingService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessRenewalJob extends Job implements ShouldQueue
{
    use SerializesModels;
    
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min
    
    public function __construct(private ShopOrder $order) {}
    
    public function handle(BillingService $billingService): void
    {
        $billingService->renewOrder($this->order);
    }
}
```

#### SuspendOrderJob
```php
namespace Pterodactyl\Jobs\Shop;

use Pterodactyl\Jobs\Job;
use Pterodactyl\Models\Shop\ShopOrder;
use Pterodactyl\Services\Servers\SuspensionService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SuspendOrderJob extends Job implements ShouldQueue
{
    use SerializesModels;
    
    public function __construct(private ShopOrder $order) {}
    
    public function handle(SuspensionService $suspensionService): void
    {
        if ($this->order->server && $this->order->status === 'suspended') {
            $suspensionService->toggle($this->order->server, 'suspend');
        }
    }
}
```

### 7.2 Scheduled Tasks

#### BillingTasksKernel
```php
// Add to app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Process renewals every hour
    $schedule->call(function () {
        app(BillingService::class)->processRenewals();
    })->hourly();
    
    // Process grace period expirations every 6 hours
    $schedule->call(function () {
        app(BillingService::class)->processGracePeriodExpirations();
    })->everySixHours();
    
    // Send renewal reminders daily at 9 AM
    $schedule->call(function () {
        app(NotificationService::class)->sendRenewalReminders();
    })->dailyAt('09:00');
}
```

## Phase 8: Testing Strategy

### 8.1 Unit Tests

Following existing test patterns:

#### ShopOrderServiceTest
```php
namespace Tests\Unit\Services\Shop;

use Tests\TestCase;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Shop\ShopPlan;
use Pterodactyl\Services\Shop\ShopOrderService;
use Pterodactyl\Services\Shop\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShopOrderServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private ShopOrderService $orderService;
    private WalletService $walletService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderService = app(ShopOrderService::class);
        $this->walletService = app(WalletService::class);
    }
    
    public function testOrderCreationWithSufficientBalance(): void
    {
        $user = User::factory()->create();
        $plan = ShopPlan::factory()->create(['price_monthly' => 10.00]);
        
        $this->walletService->credit($user, 20.00, 'Test credit');
        
        $order = $this->orderService->createOrder($user, $plan, [
            'billing_cycle' => 'monthly',
            'payment_method' => 'wallet'
        ]);
        
        $this->assertDatabaseHas('shop_orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'processing',
            'amount' => 10.00,
        ]);
        
        $this->assertEquals(10.00, $this->walletService->getBalance($user));
    }
    
    public function testOrderCreationFailsWithInsufficientBalance(): void
    {
        $user = User::factory()->create();
        $plan = ShopPlan::factory()->create(['price_monthly' => 10.00]);
        
        $this->walletService->credit($user, 5.00, 'Test credit');
        
        $this->expectException(DisplayException::class);
        $this->expectExceptionMessage('Insufficient wallet balance');
        
        $this->orderService->createOrder($user, $plan, [
            'billing_cycle' => 'monthly',
            'payment_method' => 'wallet'
        ]);
    }
    
    public function testOrderCreationRespectsUserLimits(): void
    {
        $user = User::factory()->create();
        $plan = ShopPlan::factory()->create([
            'price_monthly' => 10.00,
            'max_per_user' => 1
        ]);
        
        // Create first order
        ShopOrder::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active'
        ]);
        
        $this->expectException(DisplayException::class);
        $this->expectExceptionMessage('maximum number of this plan type');
        
        $this->orderService->createOrder($user, $plan, [
            'billing_cycle' => 'monthly',
            'payment_method' => 'wallet'
        ]);
    }
}
```

### 8.2 Feature Tests

#### ShopControllerTest
```php
namespace Tests\Feature\Http\Controllers\Base\Shop;

use Tests\TestCase;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Shop\ShopProduct;
use Pterodactyl\Models\Shop\ShopPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShopControllerTest extends TestCase
{
    use RefreshDatabase;
    
    public function testShopIndexDisplaysActiveProducts(): void
    {
        $user = User::factory()->create();
        $product = ShopProduct::factory()->create(['status' => 'active']);
        $plan = ShopPlan::factory()->create([
            'product_id' => $product->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($user)->get('/shop');
        
        $response->assertStatus(200);
        $response->assertSee($product->name);
        $response->assertSee($plan->name);
    }
    
    public function testCheckoutRequiresAuthentication(): void
    {
        $plan = ShopPlan::factory()->create();
        
        $response = $this->get("/shop/checkout/{$plan->uuid}");
        
        $response->assertRedirect('/auth/login');
    }
    
    public function testCheckoutDisplaysCorrectInformation(): void
    {
        $user = User::factory()->create();
        $plan = ShopPlan::factory()->create([
            'price_monthly' => 15.99,
            'memory' => 2048,
            'disk' => 10240
        ]);
        
        $response = $this->actingAs($user)->get("/shop/checkout/{$plan->uuid}");
        
        $response->assertStatus(200);
        $response->assertSee($plan->name);
        $response->assertSee('$15.99');
        $response->assertSee('2048 MB');
    }
}
```

## Addon Distribution & Deployment Strategy

### Distribution Methods

#### 1. Composer Package (Recommended)
```bash
# For end users to install
composer require pterodactyl-addons/shop-system

# Publish configuration
php artisan vendor:publish --tag=shop-config

# Run migrations
php artisan migrate

# Clear caches
php artisan config:clear && php artisan route:clear
```

#### 2. Manual Installation
```bash
# Download and extract to addons directory
mkdir -p /var/www/pterodactyl/addons
cd /var/www/pterodactyl/addons
git clone https://github.com/your-org/pterodactyl-shop-system.git shop

# Create symlinks for integration
ln -s /var/www/pterodactyl/addons/shop/config/shop.php /var/www/pterodactyl/config/
ln -s /var/www/pterodactyl/addons/shop/resources/views /var/www/pterodactyl/resources/views/vendor/shop

# Register service provider manually
echo "PterodactylAddons\ShopSystem\ShopServiceProvider::class," >> config/app.php

# Run installation steps
php artisan migrate --path=addons/shop/database/migrations
```

#### 3. Docker Integration
```dockerfile
# Extend existing Pterodactyl Docker image
FROM ghcr.io/pterodactyl/panel:latest

# Install shop addon
COPY --from=shop-addon /app/shop-system /var/www/html/vendor/pterodactyl-addons/shop-system

# Update composer autoload
RUN composer dump-autoload

# Set permissions
RUN chown -R www-data:www-data /var/www/html/vendor/pterodactyl-addons
```

### Update Mechanism

#### Automated Updates via Composer
```php
// src/Commands/UpdateShopCommand.php
namespace PterodactylAddons\ShopSystem\Commands;

use Illuminate\Console\Command;

class UpdateShopCommand extends Command
{
    protected $signature = 'shop:update {--force : Force update even if risky}';
    protected $description = 'Update the shop system to the latest version';

    public function handle(): int
    {
        $this->info('🔄 Updating Pterodactyl Shop System...');

        // Check for breaking changes
        if (!$this->option('force') && $this->hasBreakingChanges()) {
            $this->error('❌ Breaking changes detected. Use --force to proceed.');
            return 1;
        }

        // Backup current configuration
        $this->backupConfiguration();

        // Update via composer
        exec('composer update pterodactyl-addons/shop-system', $output, $returnCode);

        if ($returnCode === 0) {
            // Run migrations
            $this->call('migrate', ['--path' => 'vendor/pterodactyl-addons/shop-system/database/migrations']);
            
            // Clear caches
            $this->call('config:clear');
            $this->call('route:clear');
            
            $this->info('✅ Shop system updated successfully!');
            return 0;
        }

        $this->error('❌ Update failed. Please check logs.');
        return 1;
    }
}
```

### Uninstallation Process

#### Clean Removal Script
```php
// src/Commands/UninstallShopCommand.php
namespace PterodactylAddons\ShopSystem\Commands;

class UninstallShopCommand extends Command
{
    protected $signature = 'shop:uninstall {--keep-data : Keep shop data in database}';

    public function handle(): int
    {
        if (!$this->confirm('Are you sure you want to uninstall the shop system?')) {
            return 1;
        }

        // Remove shop data unless --keep-data is specified
        if (!$this->option('keep-data')) {
            $this->warn('This will permanently delete all shop data!');
            if ($this->confirm('Continue?')) {
                $this->dropShopTables();
            }
        }

        // Remove published assets
        $this->removePublishedFiles();

        // Clear caches
        $this->call('config:clear');
        $this->call('route:clear');

        $this->info('✅ Shop system uninstalled. Remove package with: composer remove pterodactyl-addons/shop-system');
        
        return 0;
    }
}
```

### Multi-Panel License Management

#### License Validation System
```php
// src/Services/LicenseValidationService.php
namespace PterodactylAddons\ShopSystem\Services;

class LicenseValidationService
{
    private string $licenseServer = 'https://license.yourorg.com/api/v1';

    public function validateLicense(string $licenseKey, string $domain): bool
    {
        $response = Http::post($this->licenseServer . '/validate', [
            'license_key' => $licenseKey,
            'domain' => $domain,
            'product' => 'pterodactyl-shop-system',
        ]);

        return $response->successful() && $response->json('valid') === true;
    }

    public function checkLicenseStatus(): array
    {
        $licenseKey = config('shop.license_key');
        $domain = request()->getHost();

        if (!$licenseKey) {
            return ['status' => 'missing', 'message' => 'No license key configured'];
        }

        if (!$this->validateLicense($licenseKey, $domain)) {
            return ['status' => 'invalid', 'message' => 'License validation failed'];
        }

        return ['status' => 'valid', 'message' => 'License is valid'];
    }
}
```

### Configuration Template for Panel Owners

#### config/shop.php (Published Version)
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shop System License
    |--------------------------------------------------------------------------
    | 
    | Your shop system license key. Required for production use.
    | Get your license at: https://yourorg.com/shop-system
    |
    */
    'license_key' => env('SHOP_LICENSE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Shop System Status
    |--------------------------------------------------------------------------
    */
    'enabled' => env('SHOP_ENABLED', false),
    'maintenance_mode' => env('SHOP_MAINTENANCE', false),
    'maintenance_message' => env('SHOP_MAINTENANCE_MESSAGE', 'Shop is temporarily unavailable.'),

    /*
    |--------------------------------------------------------------------------
    | Branding & Customization
    |--------------------------------------------------------------------------
    */
    'branding' => [
        'name' => env('SHOP_NAME', 'Server Shop'),
        'logo' => env('SHOP_LOGO', '/assets/img/pterodactyl.svg'),
        'primary_color' => env('SHOP_PRIMARY_COLOR', '#0ea5e9'),
        'custom_css' => env('SHOP_CUSTOM_CSS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    */
    'currency' => [
        'default' => env('SHOP_CURRENCY', 'USD'),
        'symbol' => env('SHOP_CURRENCY_SYMBOL', '$'),
        'precision' => (int) env('SHOP_CURRENCY_PRECISION', 2),
        'position' => env('SHOP_CURRENCY_POSITION', 'before'), // 'before' or 'after'
    ],

    // ... rest of configuration with environment variable support
];
```

### Documentation for Panel Owners

#### README.md for Installation
```markdown
# Pterodactyl Shop System

A complete billing and server provisioning addon for Pterodactyl Panel.

## 🚀 Quick Installation

### Requirements
- Pterodactyl Panel 1.11.0+
- PHP 8.1+
- MySQL/MariaDB or PostgreSQL

### Via Composer (Recommended)
```bash
cd /var/www/pterodactyl
composer require pterodactyl-addons/shop-system
php artisan vendor:publish --tag=shop-config
php artisan migrate
php artisan config:clear
```

### Configuration
1. Edit `config/shop.php`
2. Set your license key: `SHOP_LICENSE_KEY=your-license-key`
3. Enable the shop: `SHOP_ENABLED=true`
4. Configure payment gateways in admin panel

## 🎨 Customization

### Custom Styling
```bash
php artisan vendor:publish --tag=shop-views
php artisan vendor:publish --tag=shop-assets
```

### Environment Variables
```env
SHOP_ENABLED=true
SHOP_CURRENCY=USD
SHOP_CURRENCY_SYMBOL=$
SHOP_MINIMUM_DEPOSIT=5.00
```

## 📋 Features
- ✅ Multiple payment gateways (Stripe, PayPal)
- ✅ Wallet system with transactions
- ✅ Automated server provisioning
- ✅ Subscription management
- ✅ Admin dashboard
- ✅ Multi-currency support
- ✅ Customizable themes

## 🔧 Support
- Documentation: https://docs.yourorg.com/shop-system
- Support: https://support.yourorg.com
- Issues: https://github.com/your-org/pterodactyl-shop-system/issues
```

## Implementation Timeline & Phases

### Phase 0: Addon Infrastructure (Week 1)
- Set up Composer package structure
- Create service provider and auto-discovery
- Implement compatibility layer for multiple Pterodactyl versions
- Create installation and update scripts
- Set up license validation system

### Phase 1: Foundation (Weeks 2-3)
- Create database migrations with proper prefixing
- Implement core models with addon namespacing
- Set up repository contracts and implementations
- Create configuration files with environment variable support
- Implement basic routing structure

### Phase 2: Core Services (Weeks 4-5)
- Implement ShopOrderService with addon architecture
- Create WalletService with proper isolation
- Build BillingService with scheduled task integration
- Set up payment processing framework with gateway abstractions

### Phase 3: Admin Interface (Weeks 6-7)
- Create admin controllers with proper namespacing
- Build admin views with publishable templates
- Implement validation requests following Laravel standards
- Add admin routes with middleware integration
- Create admin navigation hooks

### Phase 4: User Interface (Weeks 8-9)
- Build shop frontend controllers with theming support
- Create user-facing views with customization options
- Implement checkout process with multiple payment methods
- Add client-side validation and AJAX functionality
- Implement responsive design

### Phase 5: Integration & Automation (Weeks 10-11)
- Implement queue jobs with proper job isolation
- Set up scheduled tasks through service provider
- Integrate with existing Pterodactyl server creation services
- Add activity logging integration
- Create webhook handlers for payment gateways

### Phase 6: Packaging & Distribution (Weeks 12-13)
- Finalize Composer package configuration
- Create installation and update scripts
- Implement license validation system
- Set up automated testing pipeline
- Create distribution documentation

### Phase 7: Security & Testing (Weeks 14-15)
- Implement authorization policies with proper gates
- Add comprehensive test coverage for addon functionality
- Security audit focusing on payment security
- Performance optimization for addon architecture
- Cross-version compatibility testing

### Phase 8: Documentation & Launch (Weeks 16-17)
- Create comprehensive installation documentation
- Build user and admin guides
- Create video tutorials for setup
- Set up support infrastructure
- Launch on Composer/Packagist
- Create marketplace presence

## Key Success Metrics

1. **Code Quality**: 100% adherence to existing Pterodactyl patterns
2. **Security**: Zero hardcoded values, proper authorization on all endpoints
3. **Performance**: Sub-200ms response times for shop pages
4. **Test Coverage**: Minimum 80% code coverage
5. **Integration**: Seamless integration with existing server management
6. **Scalability**: Support for 10,000+ concurrent users

## 🔥 **Additional Critical Components to Implement**

### 9. Enhanced Server Management Integration

#### Egg/Nest Selection & Templates
```php
// src/Models/Shop/ServerTemplate.php
class ServerTemplate extends Model
{
    protected $fillable = [
        'name', 'description', 'egg_id', 'startup_command', 
        'environment_variables', 'file_mounts', 'category'
    ];
    
    protected $casts = [
        'environment_variables' => 'array',
        'file_mounts' => 'array',
    ];
    
    public function egg(): BelongsTo
    {
        return $this->belongsTo(\Pterodactyl\Models\Egg::class);
    }
}
```

#### Dynamic Resource Allocation
```php
// src/Services/ResourceAllocationService.php
class ResourceAllocationService
{
    public function findOptimalNode(ShopPlan $plan, ?int $locationId = null): Node
    {
        return Node::where('maintenance', false)
            ->whereHas('allocations', function ($query) {
                $query->whereNull('server_id');
            })
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->orderByRaw('(memory - memory_overallocate) DESC')
            ->orderByRaw('(disk - disk_overallocate) DESC')
            ->firstOrFail();
    }
    
    public function reserveResources(Node $node, ShopPlan $plan): void
    {
        // Temporarily reserve resources to prevent overselling
        Cache::put("node_reservation_{$node->id}", [
            'memory' => $plan->memory,
            'disk' => $plan->disk,
            'reserved_until' => now()->addMinutes(15)
        ], 900);
    }
}
```

### 10. Advanced Coupon & Promotion System

#### Coupon Models and Logic
```php
// src/Models/Shop/Coupon.php
class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'minimum_order_amount', 'usage_limit',
        'valid_from', 'valid_until', 'applicable_plans', 'status'
    ];
    
    protected $casts = [
        'value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'applicable_plans' => 'array',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];
    
    public function isValidForOrder(ShopOrder $order): bool
    {
        // Validation logic for coupon applicability
        if ($this->status !== 'active') return false;
        if ($this->valid_from && now()->isBefore($this->valid_from)) return false;
        if ($this->valid_until && now()->isAfter($this->valid_until)) return false;
        if ($this->minimum_order_amount && $order->amount < $this->minimum_order_amount) return false;
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) return false;
        if (!empty($this->applicable_plans) && !in_array($order->plan_id, $this->applicable_plans)) return false;
        
        return true;
    }
    
    public function calculateDiscount(float $orderAmount): float
    {
        return match($this->type) {
            'percentage' => $orderAmount * ($this->value / 100),
            'fixed_amount' => min($this->value, $orderAmount),
            default => 0
        };
    }
}
```

### 11. Comprehensive Tax Management

#### Tax Calculation Service
```php
// src/Services/TaxCalculationService.php
class TaxCalculationService
{
    public function calculateTax(float $amount, User $user): array
    {
        $taxRate = $this->getTaxRateForUser($user);
        
        if (!$taxRate) {
            return ['tax_amount' => 0, 'tax_rate' => 0, 'tax_name' => null];
        }
        
        $taxAmount = $amount * $taxRate->tax_rate;
        
        return [
            'tax_amount' => round($taxAmount, 2),
            'tax_rate' => $taxRate->tax_rate,
            'tax_name' => $taxRate->tax_name,
            'tax_country' => $taxRate->country_code,
        ];
    }
    
    private function getTaxRateForUser(User $user): ?TaxRate
    {
        // Get user's country from IP or profile
        $country = $this->getUserCountry($user);
        
        return TaxRate::where('country_code', $country)
            ->where('status', 'active')
            ->first();
    }
}
```

### 12. Resource Usage Monitoring & Analytics

#### Usage Tracking System
```php
// src/Services/ResourceMonitoringService.php
class ResourceMonitoringService
{
    public function recordUsage(ShopOrder $order): void
    {
        if (!$order->server) return;
        
        $stats = $this->fetchServerStats($order->server);
        
        ResourceUsage::create([
            'order_id' => $order->id,
            'recorded_at' => now(),
            'cpu_usage' => $stats['cpu_absolute'],
            'memory_usage' => $stats['memory_bytes'],
            'disk_usage' => $stats['disk_bytes'],
            'network_rx' => $stats['network_rx_bytes'],
            'network_tx' => $stats['network_tx_bytes'],
        ]);
    }
    
    public function generateUsageReport(ShopOrder $order, Carbon $from, Carbon $to): array
    {
        return ResourceUsage::where('order_id', $order->id)
            ->whereBetween('recorded_at', [$from, $to])
            ->selectRaw('
                AVG(cpu_usage) as avg_cpu,
                MAX(cpu_usage) as max_cpu,
                AVG(memory_usage) as avg_memory,
                MAX(memory_usage) as max_memory,
                SUM(network_rx) as total_rx,
                SUM(network_tx) as total_tx
            ')
            ->first()
            ->toArray();
    }
}
```

### 13. Advanced Notification System

#### Notification Template Engine
```php
// src/Services/NotificationService.php
class NotificationService
{
    public function sendOrderConfirmation(ShopOrder $order): void
    {
        $template = NotificationTemplate::where('type', 'order_created')
            ->where('channel', 'email')
            ->first();
            
        $variables = [
            'user_name' => $order->user->name,
            'order_uuid' => $order->uuid,
            'plan_name' => $order->plan->name,
            'amount' => $order->amount,
            'next_due_date' => $order->next_due_at->format('M d, Y'),
        ];
        
        $subject = $this->parseTemplate($template->subject, $variables);
        $body = $this->parseTemplate($template->body_template, $variables);
        
        Mail::to($order->user->email)->send(new OrderConfirmationMail($subject, $body));
    }
    
    public function sendRenewalReminder(ShopOrder $order, int $daysBefore): void
    {
        // Similar template-based notification logic
    }
}
```

### 14. Referral & Affiliate System

#### Complete Referral Implementation
```php
// src/Services/ReferralService.php
class ReferralService
{
    public function trackReferral(User $referrer, User $referred): Referral
    {
        return Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'commission_rate' => config('shop.referral.default_rate', 0.10),
            'status' => 'active',
        ]);
    }
    
    public function processCommission(ShopOrder $order): void
    {
        $referral = Referral::where('referred_id', $order->user_id)
            ->where('status', 'active')
            ->first();
            
        if (!$referral) return;
        
        $commissionAmount = $order->amount * $referral->commission_rate;
        
        // Credit referrer's wallet
        $this->walletService->credit(
            $referral->referrer,
            $commissionAmount,
            "Referral commission from order #{$order->uuid}"
        );
        
        // Record the earning
        ReferralEarning::create([
            'referral_id' => $referral->id,
            'order_id' => $order->id,
            'commission_amount' => $commissionAmount,
        ]);
        
        $referral->increment('total_earned', $commissionAmount);
    }
}
```

### 15. API Rate Limiting & Security Enhancements

#### Enhanced Security Middleware
```php
// src/Http/Middleware/ShopRateLimiting.php
class ShopRateLimiting
{
    public function handle($request, $next, $maxAttempts = 5, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new ThrottleRequestsException('Too many payment attempts');
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        
        return $next($request);
    }
}

// src/Http/Middleware/PaymentSecurityMiddleware.php
class PaymentSecurityMiddleware
{
    public function handle($request, $next)
    {
        // Verify payment signatures
        if ($request->routeIs('shop.payment.*')) {
            $this->verifyPaymentIntegrity($request);
        }
        
        // Block suspicious IPs
        if ($this->isSuspiciousIP($request->ip())) {
            abort(403, 'Access denied from this location');
        }
        
        return $next($request);
    }
}
```

### 16. Advanced Analytics & Reporting

#### Revenue Analytics Service
```php
// src/Services/AnalyticsService.php
class AnalyticsService
{
    public function getRevenueSummary(Carbon $from, Carbon $to): array
    {
        return [
            'total_revenue' => $this->getTotalRevenue($from, $to),
            'new_customers' => $this->getNewCustomersCount($from, $to),
            'churn_rate' => $this->calculateChurnRate($from, $to),
            'avg_order_value' => $this->getAverageOrderValue($from, $to),
            'popular_plans' => $this->getPopularPlans($from, $to),
            'payment_methods' => $this->getPaymentMethodDistribution($from, $to),
        ];
    }
    
    public function generateRevenueReport(): array
    {
        return [
            'monthly_recurring_revenue' => $this->getMRR(),
            'annual_recurring_revenue' => $this->getARR(),
            'customer_lifetime_value' => $this->getCLV(),
            'revenue_growth_rate' => $this->getRevenueGrowthRate(),
        ];
    }
}
```

## Risk Mitigation

1. **Data Integrity**: Use database transactions for all financial operations
2. **Payment Security**: Implement PCI-compliant payment processing with fraud detection
3. **Performance**: Implement proper caching and database indexing with query optimization
4. **Backup Strategy**: Regular automated backups of all shop data with point-in-time recovery
5. **Monitoring**: Comprehensive logging and alerting for all shop operations
6. **Rate Limiting**: Protect payment endpoints from abuse and DDoS attacks
7. **Geographic Compliance**: Handle GDPR, PCI-DSS, and regional tax requirements
8. **Fraud Prevention**: IP blocking, velocity checks, and payment validation
9. **Resource Monitoring**: Track server usage to prevent overselling and optimize allocation
10. **Scalability Planning**: Design for horizontal scaling with load balancing support

This comprehensive plan provides a robust foundation for implementing a shop system that seamlessly integrates with Pterodactyl's existing architecture while maintaining the highest standards of code quality, security, and performance.
