<?php

namespace PterodactylAddons\ShopSystem\Services;

class VersionService
{
    /**
     * Get the current shop system version.
     */
    public static function getVersion(): string
    {
        $versionFile = __DIR__ . '/../../VERSION';
        
        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }
        
        // Fallback to composer.json version
        $composerFile = __DIR__ . '/../../composer.json';
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            return $composer['version'] ?? '1.0.0';
        }
        
        return '1.0.0';
    }
    
    /**
     * Get version with build info.
     */
    public static function getFullVersion(): array
    {
        return [
            'version' => self::getVersion(),
            'build_date' => self::getBuildDate(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }
    
    /**
     * Get the build date (last modification of VERSION file).
     */
    public static function getBuildDate(): string
    {
        $versionFile = __DIR__ . '/../../VERSION';
        
        if (file_exists($versionFile)) {
            return date('Y-m-d H:i:s', filemtime($versionFile));
        }
        
        return 'Unknown';
    }
    
    /**
     * Check if this version is newer than another version.
     */
    public static function isNewerThan(string $compareVersion): bool
    {
        return version_compare(self::getVersion(), $compareVersion, '>');
    }
}