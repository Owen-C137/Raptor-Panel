<?php

namespace PterodactylAddons\ShopSystem\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class ShopUninstallCommand extends Command
{
    protected $signature = 'shop:uninstall 
                            {--keep-data : Keep shop data in database}
                            {--force : Force uninstall without confirmation}';
    protected $description = 'Uninstall the Pterodactyl Shop System addon';

    public function handle(): int
    {
        $this->warn('🗑️ Uninstalling Pterodactyl Shop System...');
        $this->newLine();

        try {
            // Check if shop is installed
            if (!$this->isShopInstalled()) {
                $this->error('❌ Shop system is not installed.');
                return 1;
            }

            // Confirm uninstallation
            if (!$this->option('force')) {
                $this->warn('⚠️  This will uninstall the shop system and optionally remove all data.');
                if (!$this->confirm('Are you sure you want to continue?')) {
                    $this->info('Uninstall cancelled.');
                    return 0;
                }
            }

            // Step 1: Handle data removal
            if (!$this->option('keep-data')) {
                if ($this->option('force') || $this->confirm('🗃️  Delete all shop data from database? (This cannot be undone)')) {
                    $this->info('🗄️  Removing shop database tables...');
                    $this->dropShopTables();
                }
            } else {
                $this->info('📦 Keeping shop data in database...');
            }

            // Step 2: Remove configuration files
            $this->info('🗂️  Removing configuration files...');
            $this->removeConfigFiles();

            // Step 3: Remove service provider registration
            $this->info('🔧 Cleaning service provider registration...');
            $this->cleanServiceProvider();

            // Step 4: Clear caches
            $this->info('🧹 Clearing application caches...');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            $this->newLine();
            $this->info('✅ Shop system uninstalled successfully!');
            $this->newLine();
            $this->line('🧹 <fg=green>Pterodactyl Shop System has been removed.</fg=green>');
            $this->line('📁 Addon files remain in: <fg=cyan>addons/shop-system/</fg=cyan>');
            if ($this->option('keep-data')) {
                $this->line('💾 Database tables preserved as requested.');
            }
            $this->newLine();
            
            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Uninstall failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check if shop system is installed
     */
    protected function isShopInstalled(): bool
    {
        return File::exists(config_path('shop.php')) || 
               Schema::hasTable('shop_settings');
    }

    /**
     * Drop all shop-related database tables
     */
    protected function dropShopTables(): bool
    {
        $tables = [
            'shop_settings',
            'shop_cart_items',
            'shop_cart',
            'shop_order_items',
            'shop_coupon_usage',
            'shop_coupons',
            'shop_payments',
            'shop_orders',
            'shop_products',
            'shop_plans',
            'wallet_transactions',
            'user_wallets',
            'shop_categories',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }

        return true;
    }

    /**
     * Remove configuration files
     */
    protected function removeConfigFiles(): bool
    {
        $configFiles = [
            config_path('shop.php'),
        ];

        foreach ($configFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        return true;
    }

    /**
     * Clean up service provider registration
     */
    protected function cleanServiceProvider(): bool
    {
        $serviceProviderPath = app_path('Providers/ShopServiceProvider.php');
        
        if (File::exists($serviceProviderPath)) {
            File::delete($serviceProviderPath);
        }

        // Remove from config/app.php
        $configPath = config_path('app.php');
        if (File::exists($configPath)) {
            $content = File::get($configPath);
            
            // Remove the ShopServiceProvider registration
            $patterns = [
                "/\s*\/\*\s*\*\s*Shop System Service Provider\s*\*\/\s*\n\s*Pterodactyl\\\\Providers\\\\ShopServiceProvider::class,\s*\n/",
                "/\s*Pterodactyl\\\\Providers\\\\ShopServiceProvider::class,\s*\n/",
            ];
            
            foreach ($patterns as $pattern) {
                $content = preg_replace($pattern, '', $content);
            }
            
            File::put($configPath, $content);
        }

        return true;
    }
}
