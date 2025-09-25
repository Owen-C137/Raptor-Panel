<?php

namespace PterodactylAddons\ModManager\Commands;

use Illuminate\Console\Command;
use PterodactylAddons\ModManager\Services\CurseForgeApiService;

class PrepareCacheDirectoriesCommand extends Command
{
    protected $signature = 'mod-manager:prepare-cache 
                            {--force : Force recreation of existing directories}';
    
    protected $description = 'Pre-create cache directories to prevent harvest failures';

    public function handle(): int
    {
        $this->info('🛠️ Preparing cache directories for mod-manager...');
        
        try {
            $cacheBasePath = storage_path('framework/cache/data');
            
            if (!is_dir($cacheBasePath)) {
                mkdir($cacheBasePath, 0775, true);
                $this->info("✅ Created base cache directory: {$cacheBasePath}");
            }
            
            // Pre-create directories for common cache key patterns
            $this->createCommonCacheDirectories($cacheBasePath);
            
            // Ensure proper permissions
            $this->fixCachePermissions($cacheBasePath);
            
            $this->info('✅ Cache directories prepared successfully');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to prepare cache directories: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createCommonCacheDirectories(string $basePath): void
    {
        // Common cache key prefixes used by mod-manager
        $commonPrefixes = [
            'mod-manager:api-categories:',
            'mod-manager:api-mods:',
            'mod-manager:api-search:',
            'mod-manager:api-files:',
            'mod-manager:api-games:',
            'mod-manager:circuit-breaker:',
            'mod-manager:api-metrics:',
            'mod-manager:harvest-progress:',
            'mod-manager:deduplication:'
        ];

        $this->info('📁 Creating directories for common cache patterns...');
        $createdCount = 0;
        
        foreach ($commonPrefixes as $prefix) {
            // Generate sample hashes to determine directory structure needed
            for ($i = 0; $i < 10; $i++) {
                $sampleKey = $prefix . 'sample-' . $i;
                $hash = hash('sha1', $sampleKey);
                $firstTwo = substr($hash, 0, 2);
                $nextTwo = substr($hash, 2, 2);
                
                $firstLevelPath = $basePath . '/' . $firstTwo;
                $secondLevelPath = $firstLevelPath . '/' . $nextTwo;
                
                if (!is_dir($firstLevelPath)) {
                    mkdir($firstLevelPath, 0775, true);
                    $createdCount++;
                }
                
                if (!is_dir($secondLevelPath)) {
                    mkdir($secondLevelPath, 0775, true);
                    $createdCount++;
                }
            }
        }
        
        // Also create directories for the specific hashes that previously failed
        $knownFailureHashes = [
            '5ed9049788ef937c0fe98ff32e676c17d2a8ea5f',
            '7913f93a21a0acc2432dae493bccb75f8a80d3c6',
            '919d6ea3965bd2e11f47e599e79488ac5ca377dc',
            '5e67442e06c6476bfb82d31ef4243223b146406c',
            '717b7af1002b9092e0506c5b052cd42a63a95116'
        ];
        
        $this->info('🔧 Creating directories for known failure patterns...');
        
        foreach ($knownFailureHashes as $hash) {
            $firstTwo = substr($hash, 0, 2);
            $nextTwo = substr($hash, 2, 2);
            
            $firstLevelPath = $basePath . '/' . $firstTwo;
            $secondLevelPath = $firstLevelPath . '/' . $nextTwo;
            
            if (!is_dir($firstLevelPath)) {
                mkdir($firstLevelPath, 0775, true);
                $createdCount++;
            }
            
            if (!is_dir($secondLevelPath)) {
                mkdir($secondLevelPath, 0775, true);
                $createdCount++;
            }
        }
        
        $this->info("✅ Created {$createdCount} cache directories");
    }

    private function fixCachePermissions(string $basePath): void
    {
        $this->info('🔧 Setting proper cache permissions...');
        
        try {
            // Set ownership to www-data if running as root
            $currentUser = posix_getpwuid(posix_geteuid())['name'] ?? 'unknown';
            
            if ($currentUser === 'root') {
                // Running as root, set ownership to www-data
                exec("chown -R www-data:www-data {$basePath}");
                $this->info('✅ Set ownership to www-data');
            }
            
            // Set permissions
            exec("chmod -R 775 {$basePath}");
            $this->info('✅ Set permissions to 775');
            
        } catch (\Exception $e) {
            $this->warn('⚠️ Could not set all permissions: ' . $e->getMessage());
        }
    }
}