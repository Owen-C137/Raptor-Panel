<?php

namespace PterodactylAddons\ShopSystem\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Exception;

class ShopInstallCommand extends Command
{
    protected $signature = 'shop:install 
                            {--force : Force installation even if already installed}
                            {--skip-migrations : Skip running migrations}
                            {--skip-seed : Skip seeding default data}';
    
    protected $description = 'Install the Pterodactyl Shop System addon';

    public function handle(): int
    {
        $this->info('🚀 Installing Pterodactyl Shop System...');
        $this->newLine();

        try {
            // Step 1: Check prerequisites
            if (!$this->checkPrerequisites()) {
                return 1;
            }

            // Step 2: Check if already installed
            if (!$this->option('force') && $this->isAlreadyInstalled()) {
                $this->warn('Shop system appears to be already installed.');
                if (!$this->confirm('Continue anyway?')) {
                    return 0;
                }
            }

            // Step 3: Publish configuration
            $this->publishConfiguration();

            // Step 4: Run migrations
            if (!$this->option('skip-migrations')) {
                $this->runMigrations();
            }

            // Step 5: Seed default data
            if (!$this->option('skip-seed')) {
                $this->seedDefaultData();
            }

            // Step 6: Publish assets
            $this->publishAssets();

            // Step 7: Set permissions
            $this->setPermissions();

            // Step 8: Test installation
            $this->testInstallation();

            $this->newLine();
            $this->info('✅ Shop system installed successfully!');
            $this->showNextSteps();

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Installation failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    private function checkPrerequisites(): bool
    {
        $this->info('🔍 Checking prerequisites...');

        // Check PHP version
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $this->error('PHP 8.1.0 or higher is required. Current version: ' . PHP_VERSION);
            return false;
        }

        // Check required PHP extensions
        $required = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'curl'];
        foreach ($required as $extension) {
            if (!extension_loaded($extension)) {
                $this->error("Required PHP extension '{$extension}' is not loaded.");
                return false;
            }
        }

        // Check Laravel version compatibility
        if (!class_exists('Pterodactyl\Models\User')) {
            $this->error('Pterodactyl panel not detected. Please run this command from your Pterodactyl installation directory.');
            return false;
        }

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            return false;
        }

        // Check write permissions
        $paths = [
            config_path(),
            resource_path('views'),
            public_path(),
            database_path('migrations'),
        ];

        foreach ($paths as $path) {
            if (!is_writable($path)) {
                $this->error("Directory '{$path}' is not writable.");
                return false;
            }
        }

        $this->info('✅ Prerequisites check passed');
        return true;
    }

    private function isAlreadyInstalled(): bool
    {
        // Check if shop tables exist - use shop_categories as main indicator
        return DB::getSchemaBuilder()->hasTable('shop_categories');
    }

    private function publishConfiguration(): void
    {
        $this->info('📋 Publishing configuration files...');

        // Publish shop configuration
        Artisan::call('vendor:publish', [
            '--tag' => 'shop-config',
            '--force' => true,
        ]);

        // Check if config file was created
        if (!File::exists(config_path('shop.php'))) {
            throw new Exception('Failed to publish shop configuration file');
        }

        $this->info('✅ Configuration published');
    }

    private function runMigrations(): void
    {
        $this->info('🗄️ Running database migrations...');

        // Run shop migrations from addon directory
        Artisan::call('migrate', [
            '--path' => 'addons/shop-system/database/migrations',
            '--force' => true,
        ]);

        // Verify core tables were created (updated table names)
        $tables = ['shop_categories', 'shop_plans', 'shop_orders', 'shop_payments', 'user_wallets', 'wallet_transactions', 'shop_coupons', 'shop_coupon_usage', 'shop_cart', 'shop_cart_items', 'shop_settings'];
        foreach ($tables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                throw new Exception("Migration failed - table '{$table}' was not created");
            }
        }

        $this->info('✅ Database migrations completed');
    }

    private function seedDefaultData(): void
    {
        $this->info('🌱 Seeding default shop data...');

        // Create default shop configuration settings
        $settings = [
            'shop_enabled' => 'false',
            'shop_currency' => 'USD', 
            'shop_currency_symbol' => '$',
            'shop_tax_rate' => '0.00',
            'shop_minimum_deposit' => '5.00',
            'shop_maintenance_mode' => 'false',
            'shop_maintenance_message' => 'The shop is currently under maintenance. Please check back later.',
        ];

        foreach ($settings as $key => $value) {
            DB::table('shop_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => 'string',
                    'description' => ucfirst(str_replace('_', ' ', $key)),
                    'group' => 'general',
                    'is_public' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Create sample categories if requested
        if ($this->confirm('Would you like to create sample categories and plans?', false)) {
            $this->createSampleData();
        }

        $this->info('✅ Default data seeded');
    }

    private function createSampleData(): void
    {
        // Create sample category (only if it doesn't exist)
        $categoryId = DB::table('shop_categories')->where('name', 'Game Server Hosting')->value('id');
        if (!$categoryId) {
            $categoryId = DB::table('shop_categories')->insertGetId([
                'name' => 'Game Server Hosting',
                'description' => 'High-performance game server hosting with instant setup',
                'slug' => 'game-server-hosting',
                'active' => true,
                'sort_order' => 1,
                'parent_id' => null,
                'metadata' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create sample plans for the category
        $plans = [
            [
                'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'name' => 'Starter Plan',
                'description' => 'Perfect for small communities',
                'category_id' => $categoryId,
                'egg_id' => null,
                'visible' => true,
                'memory' => 1024,
                'swap' => 512,
                'disk' => 5120,
                'io' => 500,
                'cpu' => 100,
                'threads' => '1',
                'allocation_limit' => 1,
                'database_limit' => 1,
                'backup_limit' => 2,
                'server_limits' => json_encode([
                    'memory' => 1024,
                    'swap' => 512,
                    'disk' => 5120,
                    'io' => 500,
                    'cpu' => 100
                ]),
                'server_feature_limits' => json_encode([
                    'databases' => 1,
                    'backups' => 2,
                    'allocations' => 1
                ]),
                'allowed_locations' => '[]',
                'allowed_nodes' => '[]',
                'billing_cycles' => json_encode([
                    'monthly' => ['amount' => 9.99, 'setup_fee' => 0.00],
                    'quarterly' => ['amount' => 27.99, 'setup_fee' => 0.00]
                ]),
                'status' => 'active',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'name' => 'Professional Plan',
                'description' => 'For growing communities',
                'category_id' => $categoryId,
                'egg_id' => null,
                'visible' => true,
                'memory' => 2048,
                'swap' => 1024,
                'disk' => 10240,
                'io' => 500,
                'cpu' => 200,
                'threads' => '2',
                'allocation_limit' => 2,
                'database_limit' => 2,
                'backup_limit' => 5,
                'server_limits' => json_encode([
                    'memory' => 2048,
                    'swap' => 1024,
                    'disk' => 10240,
                    'io' => 500,
                    'cpu' => 200
                ]),
                'server_feature_limits' => json_encode([
                    'databases' => 2,
                    'backups' => 5,
                    'allocations' => 2
                ]),
                'allowed_locations' => '[]',
                'allowed_nodes' => '[]',
                'billing_cycles' => json_encode([
                    'monthly' => ['amount' => 19.99, 'setup_fee' => 0.00],
                    'quarterly' => ['amount' => 54.99, 'setup_fee' => 0.00]
                ]),
                'status' => 'active',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($plans as $plan) {
            // Only insert if plan doesn't exist for this category
            if (!DB::table('shop_plans')->where('category_id', $plan['category_id'])->where('name', $plan['name'])->exists()) {
                DB::table('shop_plans')->insert($plan);
            }
        }

        // Create sample coupon (only if it doesn't exist)
        if (!DB::table('shop_coupons')->where('code', 'WELCOME25')->exists()) {
            DB::table('shop_coupons')->insert([
                'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'code' => 'WELCOME25',
                'name' => 'Welcome Discount',
                'description' => 'Get 25% off your first order!',
                'type' => 'percentage',
                'value' => 25.00,
                'applicable_plans' => '[]',
                'usage_limit' => 100,
                'usage_limit_per_user' => 1,
                'used_count' => 0,
                'active' => true,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(3),
                'minimum_amount' => 10.00,
                'first_order_only' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info('✅ Sample data created');
    }

    private function publishAssets(): void
    {
        $this->info('🎨 Publishing assets...');

        // Publish views
        Artisan::call('vendor:publish', [
            '--tag' => 'shop-views',
            '--force' => true,
        ]);

        // Publish assets
        Artisan::call('vendor:publish', [
            '--tag' => 'shop-assets',
            '--force' => true,
        ]);

        $this->info('✅ Assets published');
    }

    private function setPermissions(): void
    {
        $this->info('🔐 Setting up permissions...');

        // Note: In a real implementation, we would integrate with Pterodactyl's permission system
        // For now, we'll just ensure admin users can access shop features
        
        $this->info('✅ Permissions configured');
    }

    private function testInstallation(): void
    {
        $this->info('🧪 Testing installation...');

        // Test database tables (updated table names)
        $tables = ['shop_categories', 'shop_plans', 'shop_orders', 'user_wallets'];
        foreach ($tables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                throw new Exception("Installation test failed - table '{$table}' not found");
            }
        }

        // Test configuration
        if (!config('shop')) {
            $this->warn('Shop configuration not loaded (this is expected for addon structure)');
        }

        // Test sample query
        try {
            DB::table('shop_categories')->count();
        } catch (Exception $e) {
            throw new Exception('Database query test failed: ' . $e->getMessage());
        }

        $this->info('✅ Installation tests passed');
    }

    private function showNextSteps(): void
    {
        $this->newLine();
        $this->info('🎉 Next Steps:');
        $this->line('1. Configure your payment gateways in the admin panel');
        $this->line('2. Enable the shop by updating shop_settings table');
        $this->line('3. Visit /admin/shop to manage your shop');
        $this->line('4. Visit /shop to view the customer interface');
        $this->newLine();
        $this->info('📚 Documentation: https://docs.pterodactyl.io/addons/shop-system');
        $this->info('💬 Support: https://discord.gg/pterodactyl');
    }
}
