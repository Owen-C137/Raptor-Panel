<?php

namespace PterodactylAddons\ShopSystem\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ShopInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'shop:install {--force : Force installation even if already installed}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Pterodactyl Shop System addon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Pterodactyl Shop System One-Click Installation...');
        $this->newLine();

        // Check if already installed
        if (!$this->option('force') && $this->isInstalled()) {
            $this->error('❌ Shop system is already installed!');
            $this->info('💡 Use --force flag to reinstall: php artisan shop:install --force');
            return 1;
        }

        try {
            // Step 1: Verify prerequisites
            $this->info('🔍 Verifying prerequisites...');
            $this->verifyPrerequisites();
            $this->line('   ✅ Prerequisites verified');

            // Step 2: Verify addon structure
            $this->info('🔍 Verifying addon structure...');
            $this->verifyAddonStructure();
            $this->line('   ✅ Addon structure verified');

            // Step 3: Install dependencies automatically
            $this->info('� Installing required dependencies...');
            $this->installDependencies();
            $this->line('   ✅ Dependencies installed');

            // Step 4: Configure PSR-4 autoloader
            $this->info('� Configuring autoloader...');
            $this->configureAutoloader();
            $this->line('   ✅ Autoloader configured');

            // Step 5: Register service provider
            $this->info('🔧 Registering service provider...');
            $this->registerServiceProvider();
            $this->line('   ✅ Service provider registered');

            // Step 6: Fix route conflicts
            $this->info('🛣️ Resolving route conflicts...');
            $this->fixRouteConflicts();
            $this->line('   ✅ Route conflicts resolved');

            // Step 7: Refresh autoloader
            $this->info('🔄 Refreshing autoloader...');
            $this->refreshAutoloader();
            $this->line('   ✅ Autoloader refreshed');

            // Step 8: Run migrations
            $this->info('📊 Running database migrations...');
            $this->runMigrations();
            $this->line('   ✅ Database migrations completed');

            // Step 9: Publish configuration
            $this->info('📁 Publishing configuration...');
            $this->publishConfiguration();
            $this->line('   ✅ Configuration published');

            // Step 10: Seed default data
            $this->info('🌱 Creating default shop settings...');
            $this->seedDefaultData();
            $this->line('   ✅ Default settings created');

            // Step 11: Clear caches
            $this->info('🧹 Clearing application caches...');
            $this->clearCaches();
            $this->line('   ✅ Caches cleared');

            // Step 12: Verify installation
            $this->info('✅ Verifying installation...');
            $this->verifyInstallation();
            $this->line('   ✅ Installation verified');

            $this->newLine();
            $this->info('🎉 <fg=green>Shop System installed successfully!</fg=green>');
            $this->newLine();
            $this->line('📊 <fg=cyan>Admin Dashboard:</fg=cyan> ' . url('/admin/shop'));
            $this->line('🛒 <fg=cyan>Customer Shop:</fg=cyan> ' . url('/shop'));
            $this->newLine();
            $this->info('💡 <fg=yellow>Next steps:</fg=yellow>');
            $this->line('   1. Visit admin dashboard to configure shop settings');
            $this->line('   2. Set up payment gateways (Stripe/PayPal)');
            $this->line('   3. Create product categories and plans');
            $this->line('   4. Test the checkout process');
            $this->newLine();

            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Installation failed: ' . $e->getMessage());
            $this->error('🔧 Check the logs for more details.');
            $this->line('   You can try running with --force to retry');
            return 1;
        }
    }

    /**
     * Check if shop system is already installed
     */
    protected function isInstalled(): bool
    {
        return File::exists(config_path('shop.php')) && 
               Schema::hasTable('shop_settings');
    }

    /**
     * Verify prerequisites are met
     */
    protected function verifyPrerequisites(): void
    {
        // Check if we're in a Pterodactyl directory
        if (!File::exists('artisan')) {
            throw new \Exception('Not in a Pterodactyl directory. Please run from your panel root.');
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '8.3.0') < 0) {
            throw new \Exception('PHP 8.3+ is required. Current version: ' . PHP_VERSION);
        }

        // Check if composer is available
        if (!shell_exec('which composer')) {
            throw new \Exception('Composer is not installed or not in PATH');
        }

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Install required dependencies
     */
    protected function installDependencies(): void
    {
        $dependencies = [
            'stripe/stripe-php:^10.0',
            'paypal/paypal-checkout-sdk:^1.0', 
            'ramsey/uuid:^4.0'
        ];

        foreach ($dependencies as $dependency) {
            $this->line("   Installing {$dependency}...");
            $result = shell_exec("composer require {$dependency} --no-interaction 2>&1");
            
            if (strpos($result, 'Installation failed') !== false) {
                throw new \Exception("Failed to install dependency: {$dependency}");
            }
        }

        // Verify PDF support exists
        $pdfCheck = shell_exec('composer show barryvdh/laravel-dompdf 2>&1');
        if (strpos($pdfCheck, 'not found') !== false) {
            $this->line('   Installing PDF support...');
            shell_exec('composer require barryvdh/laravel-dompdf:^3.1 --no-interaction');
        }
    }

    /**
     * Configure PSR-4 autoloader
     */
    protected function configureAutoloader(): void
    {
        $composerPath = base_path('composer.json');
        $composer = json_decode(File::get($composerPath), true);

        if (!isset($composer['autoload']['psr-4']['PterodactylAddons\\ShopSystem\\'])) {
            $composer['autoload']['psr-4']['PterodactylAddons\\ShopSystem\\'] = 'addons/shop-system/src/';
            
            File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * Register service provider in config/app.php
     */
    protected function registerServiceProvider(): void
    {
        $configPath = config_path('app.php');
        $configContent = File::get($configPath);
        
        if (!str_contains($configContent, 'PterodactylAddons\\ShopSystem\\ShopServiceProvider::class')) {
            // Find the ViewComposerServiceProvider line and add after it
            $pattern = '/(\s*)(Pterodactyl\\\\Providers\\\\ViewComposerServiceProvider::class,)/';
            $replacement = '$1$2' . PHP_EOL . PHP_EOL . '$1/*' . PHP_EOL . '$1 * Shop System Service Provider - Self-contained addon' . PHP_EOL . '$1 */' . PHP_EOL . '$1PterodactylAddons\\ShopSystem\\ShopServiceProvider::class,';
            
            $newContent = preg_replace($pattern, $replacement, $configContent);
            
            if ($newContent && $newContent !== $configContent) {
                File::put($configPath, $newContent);
            } else {
                throw new \Exception('Failed to register service provider in config/app.php');
            }
        }
    }

    /**
     * Fix route conflicts with base Pterodactyl routes
     */
    protected function fixRouteConflicts(): void
    {
        $routePath = base_path('routes/base.php');
        
        if (File::exists($routePath)) {
            $content = File::get($routePath);
            
            // Fix the catch-all pattern to exclude shop routes
            $oldPattern = '^(?!(\\/)?(api|auth|admin|daemon))';
            $newPattern = '^(?!(\\/)?(api|auth|admin|daemon|shop))';
            
            if (str_contains($content, $oldPattern) && !str_contains($content, $newPattern)) {
                $content = str_replace($oldPattern, $newPattern, $content);
                File::put($routePath, $content);
            }
        }
    }

    /**
     * Refresh composer autoloader
     */
    protected function refreshAutoloader(): void
    {
        shell_exec('composer dump-autoload --no-dev --optimize --quiet');
    }

    /**
     * Run database migrations
     */
    protected function runMigrations(): void
    {
        try {
            // Run migrations from the addon directory specifically
            Artisan::call('migrate', [
                '--path' => 'addons/shop-system/database/migrations',
                '--force' => true
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear all caches
     */
    protected function clearCaches(): void
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear'); 
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
    }

    /**
     * Verify installation completed successfully
     */
    protected function verifyInstallation(): void
    {
        // Check if shop commands are available
        $commands = Artisan::all();
        $shopCommands = array_filter(array_keys($commands), fn($cmd) => str_starts_with($cmd, 'shop:'));
        
        if (empty($shopCommands)) {
            throw new \Exception('Shop commands not found. Installation may have failed.');
        }

        // Check if key tables exist
        $requiredTables = ['shop_categories', 'shop_plans', 'user_wallets', 'shop_orders', 'shop_settings'];
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                throw new \Exception("Required table '{$table}' not found. Migration may have failed.");
            }
        }

        // Check if config file exists
        if (!File::exists(config_path('shop.php'))) {
            throw new \Exception('Shop configuration file not published.');
        }
    }

    /**
     * Verify addon structure exists
     */
    protected function verifyAddonStructure(): bool
    {
        $requiredPaths = [
            'addons/shop-system/src/Models',
            'addons/shop-system/src/Http/Controllers',
            'addons/shop-system/src/ShopServiceProvider.php',
            'addons/shop-system/database/migrations',
            'addons/shop-system/resources/views',
            'addons/shop-system/config/shop.php',
        ];

        foreach ($requiredPaths as $path) {
            if (!File::exists(base_path($path))) {
                throw new \Exception("Required addon path missing: {$path}");
            }
        }

        return true;
    }

    /**
     * Publish configuration files
     */
    protected function publishConfiguration(): bool
    {
        $configSource = base_path('addons/shop-system/config/shop.php');
        $configDest = config_path('shop.php');
        
        if (File::exists($configSource)) {
            File::copy($configSource, $configDest);
        }

        return true;
    }

    /**
     * Seed default shop data
     */
    protected function seedDefaultData(): bool
    {
        // Ensure the table exists before trying to seed data
        if (!\Schema::hasTable('shop_settings')) {
            throw new \Exception('shop_settings table does not exist. Migrations may have failed.');
        }

        // Create default shop settings if they don't exist
        $defaultSettings = [
            ['key' => 'shop_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable/disable the shop system', 'group' => 'general', 'is_public' => true],
            ['key' => 'shop_name', 'value' => 'Pterodactyl Shop', 'type' => 'string', 'description' => 'Name of the shop', 'group' => 'general', 'is_public' => true],
            ['key' => 'shop_currency', 'value' => 'USD', 'type' => 'string', 'description' => 'Shop currency code', 'group' => 'general', 'is_public' => true],
            ['key' => 'wallet_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable wallet system', 'group' => 'payment', 'is_public' => true],
        ];

        foreach ($defaultSettings as $setting) {
            \DB::table('shop_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        return true;
    }

    /**
     * Get the service provider content
     */
    protected function getServiceProviderContent(): string
    {
        return <<<'PHP'
<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;

class ShopServiceProvider extends ServiceProvider
{
    /**
     * Register the shop addon service provider.
     */
    public function register()
    {
        // Register the addon service provider if it exists
        if (file_exists(base_path('addons/shop-system/src/Providers/ShopServiceProvider.php'))) {
            $this->app->register(\PterodactylAddons\ShopSystem\Providers\ShopServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        //
    }
}
PHP;
    }
}
