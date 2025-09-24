<?php

namespace Pterodactyl\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Version Service
 * 
 * Manages application version from database with fallback support
 */
class VersionService
{
    private const CACHE_KEY = 'app.version';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the current application version
     */
    public function getCurrentVersion(): string
    {
        // Try cache first for performance
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function() {
            try {
                // Try to get version from database
                $version = DB::table('settings')
                    ->where('key', 'app:version')
                    ->value('value');
                
                if ($version) {
                    return $version;
                }
            } catch (\Exception $e) {
                // Database might not be available during installation/migration
                // Log error but don't fail
                \Log::debug('Failed to get version from database: ' . $e->getMessage());
            }
            
            // Fallback to config/env
            return config('app.version', env('APP_VERSION', '1.3.16'));
        });
    }

    /**
     * Update the application version in database
     */
    public function updateVersion(string $version): void
    {
        try {
            DB::table('settings')->updateOrInsert(
                ['key' => 'app:version'],
                ['value' => $version]
            );
            
            // Clear cache
            Cache::forget(self::CACHE_KEY);
            
            \Log::info("Updated application version to: {$version}");
        } catch (\Exception $e) {
            \Log::error("Failed to update version in database: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize version in database from config
     */
    public function initializeVersion(): void
    {
        try {
            $existingVersion = DB::table('settings')
                ->where('key', 'app:version')
                ->value('value');
            
            if (!$existingVersion) {
                $configVersion = config('app.version', env('APP_VERSION', '1.3.16'));
                $this->updateVersion($configVersion);
                \Log::info("Initialized version in database: {$configVersion}");
            }
        } catch (\Exception $e) {
            \Log::warning("Could not initialize version in database: " . $e->getMessage());
        }
    }

    /**
     * Get version for display (with cache bypass for admin)
     */
    public function getDisplayVersion(bool $bypassCache = false): string
    {
        if ($bypassCache) {
            Cache::forget(self::CACHE_KEY);
        }
        
        return $this->getCurrentVersion();
    }

    /**
     * Force refresh version cache (useful for update checks)
     */
    public function forceRefresh(): string
    {
        Cache::forget(self::CACHE_KEY);
        return $this->getCurrentVersion();
    }
}