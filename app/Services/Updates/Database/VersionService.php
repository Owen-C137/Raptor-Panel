<?php

namespace Pterodactyl\Services\Updates\Database;

use Carbon\Carbon;
use Pterodactyl\Exceptions\Updates\DatabaseOperationException;
use Pterodactyl\Models\Updates\PanelVersion;
use Pterodactyl\Models\Updates\UpdateSession;
use Pterodactyl\Services\Updates\BaseUpdateService;

/**
 * Version Service
 * 
 * Manages panel version information and history tracking.
 */
class VersionService extends BaseUpdateService
{
    public function getServiceName(): string
    {
        return 'Version Service';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check if database connection is available
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }

        // Check if panel_versions table exists
        if (!\Schema::hasTable('panel_versions')) {
            $errors[] = 'panel_versions table does not exist';
        }

        return $errors;
    }

    /**
     * Get the current installed panel version.
     */
    public function getCurrentVersion(): ?PanelVersion
    {
        try {
            $this->logInfo('Retrieving current panel version');

            $current = PanelVersion::where('is_current', true)->first();

            if ($current) {
                $this->logDebug('Current version found', [
                    'version' => $current->version,
                    'installed_at' => $current->installed_at
                ]);
            } else {
                $this->logWarning('No current version found in database');
            }

            return $current;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get current version');
            throw new DatabaseOperationException('Failed to retrieve current version: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get version history.
     */
    public function getVersionHistory(int $limit = 10): array
    {
        try {
            $this->logInfo('Retrieving version history', ['limit' => $limit]);

            $versions = PanelVersion::orderBy('installed_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();

            $this->logDebug('Version history retrieved', ['count' => count($versions)]);

            return $versions;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get version history');
            throw new DatabaseOperationException('Failed to retrieve version history: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a new version record.
     */
    public function createVersionRecord(array $versionData, bool $setCurrent = true): PanelVersion
    {
        try {
            $this->logInfo('Creating new version record', [
                'version' => $versionData['version'] ?? 'unknown',
                'set_current' => $setCurrent
            ]);

            // Validate required fields
            $required = ['version', 'release_url', 'checksum'];
            foreach ($required as $field) {
                if (!isset($versionData[$field])) {
                    throw new DatabaseOperationException("Required field '{$field}' is missing from version data");
                }
            }

            // Start transaction
            \DB::beginTransaction();

            try {
                // If setting as current, update existing current version
                if ($setCurrent) {
                    PanelVersion::where('is_current', true)->update(['is_current' => false]);
                }

                // Create new version record
                $version = PanelVersion::create([
                    'version' => $versionData['version'],
                    'release_url' => $versionData['release_url'],
                    'download_url' => $versionData['download_url'] ?? null,
                    'release_notes' => $versionData['release_notes'] ?? null,
                    'checksum' => $versionData['checksum'],
                    'file_size' => $versionData['file_size'] ?? null,
                    'is_prerelease' => $versionData['is_prerelease'] ?? false,
                    'is_current' => $setCurrent,
                    'installed_at' => $setCurrent ? Carbon::now() : null,
                    'metadata' => $versionData['metadata'] ?? null,
                ]);

                \DB::commit();

                $this->logInfo('Version record created successfully', [
                    'id' => $version->id,
                    'version' => $version->version,
                    'is_current' => $version->is_current
                ]);

                return $version;

            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to create version record');
            throw new DatabaseOperationException('Failed to create version record: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update the current version.
     */
    public function setCurrentVersion(string $version): bool
    {
        try {
            $this->logInfo('Setting current version', ['version' => $version]);

            \DB::beginTransaction();

            try {
                // Find the version record
                $versionRecord = PanelVersion::where('version', $version)->first();
                
                if (!$versionRecord) {
                    throw new DatabaseOperationException("Version '{$version}' not found in database");
                }

                // Update current version flags
                PanelVersion::where('is_current', true)->update(['is_current' => false]);
                
                $versionRecord->update([
                    'is_current' => true,
                    'installed_at' => Carbon::now()
                ]);

                \DB::commit();

                $this->logInfo('Current version updated successfully', [
                    'version' => $version,
                    'installed_at' => $versionRecord->installed_at
                ]);

                return true;

            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to set current version');
            throw new DatabaseOperationException('Failed to set current version: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if a version exists in the database.
     */
    public function versionExists(string $version): bool
    {
        try {
            $exists = PanelVersion::where('version', $version)->exists();
            
            $this->logDebug('Version existence check', [
                'version' => $version,
                'exists' => $exists
            ]);

            return $exists;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to check version existence');
            return false;
        }
    }

    /**
     * Compare two versions.
     */
    public function compareVersions(string $version1, string $version2): int
    {
        try {
            // Use version_compare for semantic version comparison
            $result = version_compare($version1, $version2);
            
            $this->logDebug('Version comparison', [
                'version1' => $version1,
                'version2' => $version2,
                'result' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->handleException($e, 'Version comparison failed');
            throw new DatabaseOperationException('Failed to compare versions: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the latest version from database.
     */
    public function getLatestVersion(): ?PanelVersion
    {
        try {
            $this->logInfo('Retrieving latest version from database');

            // Get all versions and sort them
            $versions = PanelVersion::all();
            
            if ($versions->isEmpty()) {
                $this->logWarning('No versions found in database');
                return null;
            }

            // Sort by semantic version
            $sorted = $versions->sort(function ($a, $b) {
                return version_compare($b->version, $a->version);
            });

            $latest = $sorted->first();

            $this->logDebug('Latest version found', [
                'version' => $latest->version,
                'is_current' => $latest->is_current
            ]);

            return $latest;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get latest version');
            throw new DatabaseOperationException('Failed to get latest version: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get version statistics.
     */
    public function getVersionStatistics(): array
    {
        try {
            $this->logInfo('Generating version statistics');

            $stats = [
                'total_versions' => PanelVersion::count(),
                'current_version' => null,
                'latest_version' => null,
                'prerelease_count' => PanelVersion::where('is_prerelease', true)->count(),
                'stable_count' => PanelVersion::where('is_prerelease', false)->count(),
                'first_install_date' => null,
                'last_update_date' => null,
            ];

            // Get current version
            $current = PanelVersion::where('is_current', true)->first();
            if ($current) {
                $stats['current_version'] = $current->version;
                $stats['last_update_date'] = $current->installed_at?->toDateTimeString();
            }

            // Get latest version
            $latest = $this->getLatestVersion();
            if ($latest) {
                $stats['latest_version'] = $latest->version;
            }

            // Get first installation date
            $first = PanelVersion::orderBy('installed_at')->whereNotNull('installed_at')->first();
            if ($first) {
                $stats['first_install_date'] = $first->installed_at->toDateTimeString();
            }

            $this->logDebug('Version statistics generated', $stats);

            return $stats;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to generate version statistics');
            throw new DatabaseOperationException('Failed to generate version statistics: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Clean up old version records.
     */
    public function cleanupOldVersions(int $keepCount = 10): int
    {
        try {
            $this->logInfo('Cleaning up old version records', ['keep_count' => $keepCount]);

            // Get versions to delete (keep the most recent ones)
            $versionsToDelete = PanelVersion::orderBy('installed_at', 'desc')
                ->skip($keepCount)
                ->get();

            $deleteCount = 0;

            foreach ($versionsToDelete as $version) {
                // Don't delete current version
                if (!$version->is_current) {
                    $version->delete();
                    $deleteCount++;
                    
                    $this->logDebug('Deleted old version record', [
                        'version' => $version->version,
                        'installed_at' => $version->installed_at
                    ]);
                }
            }

            $this->logInfo('Version cleanup completed', [
                'deleted_count' => $deleteCount,
                'kept_count' => $keepCount
            ]);

            return $deleteCount;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to cleanup old versions');
            throw new DatabaseOperationException('Failed to cleanup old versions: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the last update date from the most recent completed update session.
     */
    public function getLastUpdateDate(): ?\Carbon\Carbon
    {
        try {
            $this->logInfo('Retrieving last update date');

            // Check for the most recent completed update session
            $lastSession = UpdateSession::where('status', 'completed')
                ->whereNotNull('completed_at')
                ->orderBy('completed_at', 'desc')
                ->first();

            if ($lastSession && $lastSession->completed_at) {
                $lastUpdate = \Carbon\Carbon::parse($lastSession->completed_at);
                
                $this->logDebug('Last update date from session', [
                    'date' => $lastUpdate->toISOString(),
                    'session_id' => $lastSession->session_id,
                    'to_version' => $lastSession->to_version
                ]);

                return $lastUpdate;
            }

            // Fallback: Use panel version created_at if no sessions exist
            $currentVersion = PanelVersion::where('is_current', true)->first();
            if ($currentVersion && $currentVersion->created_at) {
                $fallbackDate = \Carbon\Carbon::parse($currentVersion->created_at);
                
                $this->logDebug('Last update date from version record (fallback)', [
                    'date' => $fallbackDate->toISOString(),
                    'version' => $currentVersion->version
                ]);

                return $fallbackDate;
            }

            $this->logDebug('No update sessions or version records found');
            return null;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get last update date');
            return null; // Return null on error instead of throwing
        }
    }
}