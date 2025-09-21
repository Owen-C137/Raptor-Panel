<?php

namespace Pterodactyl\Services\Updates;

/**
 * Backwards compatibility wrapper for the new GitHub Release Update Service
 * Maintains existing API while using the new simple release-based system
 */
class CustomUpdateService
{
    public function __construct(
        protected GitHubReleaseUpdateService $releaseUpdateService
    ) {}

    /**
     * Get current version
     */
    public function getCurrentVersion(): string
    {
        return $this->releaseUpdateService->getCurrentVersion();
    }

    /**
     * Check for updates
     */
    public function checkForUpdates(): array
    {
        return $this->releaseUpdateService->checkForUpdates();
    }

    /**
     * Check if update is available
     */
    public function isUpdateAvailable(): bool
    {
        $result = $this->checkForUpdates();
        return $result['available'] ?? false;
    }

    /**
     * Get update information
     */
    public function getUpdateInfo(): array
    {
        return $this->checkForUpdates();
    }

    /**
     * Download and apply update
     */
    public function downloadAndApplyUpdate(): array
    {
        return $this->releaseUpdateService->downloadAndApplyUpdate();
    }
}
