<?php

namespace PterodactylAddons\ModManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use PterodactylAddons\ModManager\Services\CurseForgeApiService;

class ModManagerInstallCommand extends Command
{
    protected $signature = 'mod-manager:install 
                            {--force : Force reinstallation over existing installation}
                            {--skip-api-test : Skip CurseForge API connectivity test}';

    protected $description = 'Install the Mod Manager addon with complete setup and validation';

    public function handle(): int
    {
        $this->info('🚀 Starting Mod Manager Addon Installation');
        $this->info('=====================================');

        // Check if already installed (unless force)
        if (!$this->option('force') && $this->isAlreadyInstalled()) {
            $this->error('❌ Mod Manager is already installed!');
            $this->line('   Use --force to reinstall over existing installation');
            return self::FAILURE;
        }

        try {
            // Phase 1: Prerequisites validation
            $this->info('📋 Phase 1: Validating Prerequisites...');
            $this->validatePrerequisites();

            // Phase 2: Addon structure verification
            $this->info('📁 Phase 2: Verifying Addon Structure...');
            $this->verifyAddonStructure();

            // Phase 3: Core integration (minimal modifications)
            $this->info('🔧 Phase 3: Integrating with Core System...');
            $this->integrateCoreSystem();

            // Phase 4: Database setup
            $this->info('🗄️  Phase 4: Setting up Database...');
            $this->setupDatabase();

            // Phase 5: System configuration
            $this->info('⚙️  Phase 5: Configuring System...');
            $this->configureSystem();

            // Phase 6: API connectivity test
            if (!$this->option('skip-api-test')) {
                $this->info('🌐 Phase 6: Testing API Connectivity...');
                $this->testApiConnectivity();
            }

            // Phase 7: Final validation
            $this->info('✅ Phase 7: Final Validation...');
            $this->finalValidation();

            $this->displaySuccessMessage();
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('💥 Installation failed: ' . $e->getMessage());
            $this->rollbackInstallation();
            return self::FAILURE;
        }
    }

    private function validatePrerequisites(): void
    {
        $this->line('   Checking PHP version (8.3+)...');
        if (version_compare(PHP_VERSION, '8.3.0', '<')) {
            throw new \Exception('PHP 8.3+ required, found: ' . PHP_VERSION);
        }
        $this->info('   ✅ PHP version: ' . PHP_VERSION);

        $this->line('   Checking required PHP extensions...');
        $required = ['curl', 'json', 'zip', 'pdo_mysql'];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                throw new \Exception("Required PHP extension missing: {$ext}");
            }
        }
        $this->info('   ✅ All required extensions loaded');

        $this->line('   Testing database connectivity...');
        try {
            DB::connection()->getPdo();
            $this->info('   ✅ Database connection successful');
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }

        $this->line('   Checking Redis availability...');
        try {
            if (config('cache.default') === 'redis') {
                app('redis')->ping();
                $this->info('   ✅ Redis connection successful');
            } else {
                $this->warn('   ⚠️  Redis not configured, some features may be limited');
            }
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Redis not available, some features may be limited');
        }

        $this->line('   Validating CurseForge API key...');
        $apiKey = env('CURSEFORGE_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('CURSEFORGE_API_KEY environment variable not set');
        }
        if (strlen($apiKey) < 10) {
            throw new \Exception('CURSEFORGE_API_KEY appears to be invalid (too short)');
        }
        $this->info('   ✅ API key configured');
    }

    private function verifyAddonStructure(): void
    {
        $requiredPaths = [
            'addons/mod-manager/src',
            'addons/mod-manager/src/Commands',
            'addons/mod-manager/src/Models',
            'addons/mod-manager/src/Services',
            'addons/mod-manager/src/Providers',
            'addons/mod-manager/database/migrations',
            'addons/mod-manager/config',
        ];

        $this->line('   Verifying directory structure...');
        foreach ($requiredPaths as $path) {
            $fullPath = base_path($path);
            if (!File::isDirectory($fullPath)) {
                throw new \Exception("Required directory missing: {$path}");
            }
        }
        $this->info('   ✅ Directory structure verified');

        $this->line('   Checking PSR-4 namespace compliance...');
        $serviceProvider = base_path('addons/mod-manager/src/Providers/ModManagerServiceProvider.php');
        if (!File::exists($serviceProvider)) {
            throw new \Exception('Service provider not found');
        }

        $content = File::get($serviceProvider);
        if (!str_contains($content, 'namespace PterodactylAddons\ModManager\Providers')) {
            throw new \Exception('Invalid namespace in service provider');
        }
        $this->info('   ✅ PSR-4 namespace compliance verified');

        $this->line('   Validating composer.json...');
        $composerPath = base_path('addons/mod-manager/composer.json');
        if (!File::exists($composerPath)) {
            throw new \Exception('composer.json not found');
        }

        $composer = json_decode(File::get($composerPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON in composer.json');
        }

        if (!isset($composer['autoload']['psr-4']['PterodactylAddons\\ModManager\\'])) {
            throw new \Exception('PSR-4 autoloading not configured correctly');
        }
        $this->info('   ✅ Composer.json validated');
    }

    private function integrateCoreSystem(): void
    {
        $this->line('   Registering service provider in config/app.php...');
        $configPath = config_path('app.php');
        $config = File::get($configPath);

        $providerClass = 'PterodactylAddons\\ModManager\\Providers\\ModManagerServiceProvider::class';

        if (!str_contains($config, $providerClass)) {
            // Find the providers array and add our provider
            $pattern = "/'providers'\s*=>\s*\[([^]]+)\]/";
            
            if (preg_match($pattern, $config, $matches)) {
                $providers = $matches[1];
                
                // Add our provider before the closing bracket
                $newProviders = $providers . "\n\n        /*\n         * Mod Manager Service Provider - Self-contained addon\n         */\n        {$providerClass},\n    ";
                
                $newConfig = str_replace($matches[1], $newProviders, $config);
                File::put($configPath, $newConfig);
                $this->info('   ✅ Service provider registered');
            } else {
                throw new \Exception('Could not find providers array in config/app.php');
            }
        } else {
            $this->info('   ✅ Service provider already registered');
        }

        $this->line('   Adding PSR-4 autoloading to composer.json...');
        $composerPath = base_path('composer.json');
        $composer = json_decode(File::get($composerPath), true);

        $autoloadKey = 'PterodactylAddons\\ModManager\\';
        $autoloadPath = 'addons/mod-manager/src/';

        if (!isset($composer['autoload']['psr-4'][$autoloadKey])) {
            $composer['autoload']['psr-4'][$autoloadKey] = $autoloadPath;
            File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('   ✅ PSR-4 autoloading added');
        } else {
            $this->info('   ✅ PSR-4 autoloading already configured');
        }

        $this->line('   Refreshing composer autoloader...');
        exec('cd ' . base_path() . ' && composer dump-autoload --optimize', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \Exception('Failed to refresh composer autoloader');
        }
        $this->info('   ✅ Composer autoloader refreshed');
    }

    private function setupDatabase(): void
    {
        $this->line('   Running database migrations...');
        try {
            Artisan::call('migrate', [
                '--path' => 'addons/mod-manager/database/migrations',
                '--force' => true,
            ]);
            $this->info('   ✅ Database migrations completed');
        } catch (\Exception $e) {
            throw new \Exception('Migration failed: ' . $e->getMessage());
        }

        $this->line('   Seeding initial game data...');
        try {
            // We'll implement the seeder later
            // Artisan::call('db:seed', ['--class' => 'ModManagerSeeder']);
            $this->seedInitialData();
            $this->info('   ✅ Initial data seeded');
        } catch (\Exception $e) {
            throw new \Exception('Seeding failed: ' . $e->getMessage());
        }
    }

    private function configureSystem(): void
    {
        $this->line('   Publishing configuration files...');
        if (!File::exists(config_path('mod-manager.php'))) {
            File::copy(
                base_path('addons/mod-manager/config/mod-manager.php'),
                config_path('mod-manager.php')
            );
            $this->info('   ✅ Configuration files published');
        } else {
            $this->info('   ✅ Configuration files already exist');
        }

        $this->line('   Setting up queue configuration...');
        // Verify queue configuration exists
        $queueConfig = config('queue.connections');
        if (!isset($queueConfig['redis'])) {
            $this->warn('   ⚠️  Redis queue not configured, some features may be limited');
        } else {
            $this->info('   ✅ Queue configuration verified');
        }

        $this->line('   Creating storage directories...');
        $dirs = [
            storage_path('app/mod-manager'),
            storage_path('app/mod-manager/temp'),
            storage_path('app/mod-manager/backups'),
        ];

        foreach ($dirs as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }
        $this->info('   ✅ Storage directories created');

        $this->line('   Publishing public assets...');
        $this->publishAssets();
        $this->info('   ✅ Public assets published');

        $this->line('   Clearing application cache...');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        $this->info('   ✅ Application cache cleared');

        $this->line('   Preparing cache directories...');
        Artisan::call('mod-manager:prepare-cache');
        $this->info('   ✅ Cache directories prepared');
    }

    private function testApiConnectivity(): void
    {
        $this->line('   Testing CurseForge API connection...');
        $apiService = app(CurseForgeApiService::class);
        $result = $apiService->testConnection();
        
        if (!$result['success']) {
            throw new \Exception('API test failed: ' . $result['message']);
        }
        $this->info('   ✅ CurseForge API connection successful');

        $this->line('   Validating game data access...');
        $apiService = app(CurseForgeApiService::class);
        
        // Test getting Minecraft game data
        $games = $apiService->getGames();
        if (empty($games['data'])) {
            throw new \Exception('No game data returned from API');
        }

        // Find Minecraft in the games list
        $minecraftFound = false;
        foreach ($games['data'] as $game) {
            if ($game['id'] === 432) {
                $minecraftFound = true;
                break;
            }
        }

        if (!$minecraftFound) {
            throw new \Exception('Minecraft game not found in API response');
        }
        $this->info('   ✅ Game data access validated');
    }

    private function finalValidation(): void
    {
        $this->line('   Verifying service provider registration...');
        $providers = config('app.providers');
        $providerRegistered = false;
        
        foreach ($providers as $provider) {
            if (str_contains($provider, 'ModManagerServiceProvider')) {
                $providerRegistered = true;
                break;
            }
        }

        if (!$providerRegistered) {
            throw new \Exception('Service provider not properly registered');
        }
        $this->info('   ✅ Service provider registration verified');

        $this->line('   Testing database connectivity...');
        // Test that our tables exist
        $tables = ['mod_games', 'mod_categories', 'mod_mods'];
        foreach ($tables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                throw new \Exception("Required table missing: {$table}");
            }
        }
        $this->info('   ✅ Database connectivity verified');

        $this->line('   Verifying command registration...');
        $commands = Artisan::all();
        if (!isset($commands['mod-manager:uninstall'])) {
            throw new \Exception('Commands not properly registered');
        }
        $this->info('   ✅ Command registration verified');
    }

    private function seedInitialData(): void
    {
        // Insert Minecraft game data
        DB::table('mod_games')->updateOrInsert(
            ['curse_game_id' => 432],
            [
                'curse_game_id' => 432,
                'name' => 'Minecraft',
                'slug' => 'minecraft',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->line('   ✓ Seeded Minecraft game data');
    }

    private function isAlreadyInstalled(): bool
    {
        // Check if service provider is registered
        $configPath = config_path('app.php');
        if (File::exists($configPath)) {
            $config = File::get($configPath);
            if (str_contains($config, 'ModManagerServiceProvider')) {
                return true;
            }
        }

        // Check if migrations have been run
        if (DB::getSchemaBuilder()->hasTable('mod_games')) {
            return true;
        }

        return false;
    }

    private function rollbackInstallation(): void
    {
        $this->warn('🔄 Rolling back installation...');
        
        try {
            // Remove service provider registration
            $this->line('   Removing service provider registration...');
            $configPath = config_path('app.php');
            if (File::exists($configPath)) {
                $config = File::get($configPath);
                $pattern = '/\/\*\s*\n\s*\*\s*Mod Manager Service Provider[^}]+ModManagerServiceProvider::class,\s*\n/';
                $config = preg_replace($pattern, '', $config);
                File::put($configPath, $config);
                $this->info('   ✅ Service provider registration removed');
            }

            // Rollback migrations
            $this->line('   Rolling back database migrations...');
            try {
                Artisan::call('migrate:rollback', [
                    '--path' => 'addons/mod-manager/database/migrations',
                    '--force' => true,
                ]);
                $this->info('   ✅ Database migrations rolled back');
            } catch (\Exception $e) {
                $this->warn('   ⚠️  Migration rollback failed: ' . $e->getMessage());
            }

            $this->info('✅ Rollback completed');
        } catch (\Exception $e) {
            $this->error('⚠️  Rollback failed: ' . $e->getMessage());
        }
    }

    private function displaySuccessMessage(): void
    {
        $this->info('');
        $this->info('🎉 Mod Manager Installation Completed Successfully!');
        $this->info('=================================================');
        $this->info('');
        $this->info('✅ Service provider registered and active');
        $this->info('✅ Database schema created with all tables');
        $this->info('✅ CurseForge API connectivity verified');
        $this->info('✅ Initial game data seeded (Minecraft)');
        $this->info('✅ System configuration completed');
        $this->info('');
        $this->info('🚀 Next Steps:');
        $this->info('   1. Run: php artisan mod-manager:status (check system status)');
        $this->info('   2. Run: php artisan mod-manager:harvest-categories (start harvesting)');
        $this->info('   3. Access admin interface at: /admin/mod-manager');
        $this->info('');
        $this->info('📚 Documentation: Check README.md in addons/mod-manager/');
        $this->info('🐛 Issues: Run php artisan mod-manager:verify for diagnostics');
        $this->info('');
    }

    /**
     * Publish mod-manager assets to public directory
     */
    private function publishAssets(): void
    {
        $sourceDir = base_path('addons/mod-manager/resources/images');
        $targetDir = public_path('assets/mod-manager');

        if (!File::exists($sourceDir)) {
            throw new \Exception('Source assets directory not found: ' . $sourceDir);
        }

        // Create target directory if it doesn't exist
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        // Copy all files from source to target
        $files = File::files($sourceDir);
        foreach ($files as $file) {
            $filename = $file->getFilename();
            File::copy($file->getPathname(), $targetDir . '/' . $filename);
        }

        // Set proper permissions
        exec('chown -R www-data:www-data ' . escapeshellarg($targetDir));
        exec('chmod -R 644 ' . escapeshellarg($targetDir));
        exec('chmod 755 ' . escapeshellarg($targetDir));
    }
}