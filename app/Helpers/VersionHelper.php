<?php

namespace Pterodactyl\Helpers;

use Pterodactyl\Models\Updates\PanelVersion;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class VersionHelper
{
    /**
     * Get the current panel version dynamically from database,
     * with fallback to environment variable or hardcoded version.
     * 
     * @return string
     */
    public static function getCurrentVersion(): string
    {
        try {
            // First, try to get version from database if available
            if (class_exists(PanelVersion::class) && Schema::hasTable('panel_versions')) {
                $currentVersion = PanelVersion::where('is_current', true)->first();
                if ($currentVersion) {
                    Log::debug('Version retrieved from database: ' . $currentVersion->version);
                    return $currentVersion->version;
                }
            }
        } catch (\Exception $e) {
            // Log error but continue to fallback
            Log::debug('Unable to fetch version from database: ' . $e->getMessage());
        }

        // Fallback to environment variable or hardcoded version
        $fallbackVersion = env('APP_VERSION', '1.3.6');
        Log::debug('Using fallback version: ' . $fallbackVersion);
        
        return $fallbackVersion;
    }

    /**
     * Update the current version in the database.
     * 
     * @param string $newVersion
     * @return bool
     */
    public static function setCurrentVersion(string $newVersion): bool
    {
        try {
            if (!class_exists(PanelVersion::class) || !Schema::hasTable('panel_versions')) {
                Log::warning('Cannot set version: PanelVersion model or table not available');
                return false;
            }

            // Mark all versions as not current
            PanelVersion::where('is_current', true)->update(['is_current' => false]);

            // Find or create the new version record
            $versionRecord = PanelVersion::firstOrCreate(
                ['version' => $newVersion],
                [
                    'is_current' => true,
                ]
            );

            // Ensure it's marked as current
            $versionRecord->update(['is_current' => true]);

            Log::info('Successfully updated current version to: ' . $newVersion);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to set current version: ' . $e->getMessage());
            return false;
        }
    }
}