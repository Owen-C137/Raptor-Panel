<?php

namespace PterodactylAddons\ShopSystem\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

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
        $this->info('🚀 Installing Pterodactyl Shop System...');
        $this->newLine();

        // Check if already installed
        if (!$this->option('force') && $this->isInstalled()) {
            $this->error('❌ Shop system is already installed!');
            $this->info('💡 Use --force flag to reinstall: php artisan shop:install --force');
            return 1;
        }

        try {
            // Step 1: Verify addon structure
            $this->task('Verifying addon structure', function () {
                return $this->verifyAddonStructure();
            });

            // Step 2: Register service provider if not already registered
            $this->task('Registering service provider', function () {
                return $this->registerServiceProvider();
            });

            // Step 3: Run migrations
            $this->task('Running database migrations', function () {
                Artisan::call('migrate', ['--force' => true]);
                return true;
            });

            // Step 4: Publish configuration
            $this->task('Publishing configuration', function () {
                return $this->publishConfiguration();
            });

            // Step 5: Seed default data
            $this->task('Creating default shop settings', function () {
                return $this->seedDefaultData();
            });

            // Step 6: Clear caches
            $this->task('Clearing application caches', function () {
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                return true;
            });

            $this->newLine();
            $this->info('✅ Shop System installed successfully!');
            $this->newLine();
            $this->line('🎉 <fg=green>Pterodactyl Shop System is now ready!</fg=green>');
            $this->line('📊 Admin Dashboard: <fg=cyan>/admin/shop</fg=cyan>');
            $this->line('🛒 Shop Frontend: <fg=cyan>/shop</fg=cyan>');
            $this->newLine();
            $this->info('💡 Next steps:');
            $this->line('   1. Configure shop settings in admin dashboard');
            $this->line('   2. Add product categories and products');
            $this->line('   3. Configure payment methods');
            $this->newLine();

            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Installation failed: ' . $e->getMessage());
            $this->error('🔧 Please check the logs and try again.');
            return 1;
        }
    }

    /**
     * Check if shop system is already installed
     */
    protected function isInstalled(): bool
    {
        return File::exists(config_path('shop.php')) && 
               \Schema::hasTable('shop_settings');
    }

    /**
     * Verify addon structure exists
     */
    protected function verifyAddonStructure(): bool
    {
        $requiredPaths = [
            'addons/shop-system/src/Models',
            'addons/shop-system/src/Http/Controllers',
            'addons/shop-system/src/Providers/ShopServiceProvider.php',
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
     * Register the shop service provider
     */
    protected function registerServiceProvider(): bool
    {
        $providersPath = app_path('Providers/ShopServiceProvider.php');
        
        if (!File::exists($providersPath)) {
            // Create the minimal service provider wrapper
            $serviceProviderContent = $this->getServiceProviderContent();
            File::put($providersPath, $serviceProviderContent);
        }

        // Check if registered in config/app.php
        $configPath = config_path('app.php');
        $configContent = File::get($configPath);
        
        if (!str_contains($configContent, 'ShopServiceProvider::class')) {
            // Add to providers array
            $pattern = "/(Pterodactyl\\\\Providers\\\\EventServiceProvider::class,)/";
            $replacement = "$1\n        \n        /*\n         * Shop System Service Provider\n         */\n        Pterodactyl\\Providers\\ShopServiceProvider::class,";
            
            $newContent = preg_replace($pattern, $replacement, $configContent);
            File::put($configPath, $newContent);
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
