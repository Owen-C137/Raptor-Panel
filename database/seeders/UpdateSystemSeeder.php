<?php

namespace Database\Seeders;

use Pterodactyl\Models\Updates\PanelVersion;
use Pterodactyl\Models\Updates\UpdateSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateSystemSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds for the update system.
     * 
     * This seeder can be used to reset the update system to defaults
     * or to seed data in testing environments.
     */
    public function run(): void
    {
        $this->seedCurrentVersion();
        $this->seedDefaultSettings();
    }

    /**
     * Seed the current panel version
     */
    private function seedCurrentVersion(): void
    {
        PanelVersion::updateOrCreate(
            ['version' => '1.3.0'],
            [
                'is_current' => true,
                'release_date' => now(),
                'release_notes' => 'Current installed version of Raptor Panel',
                'changelog_data' => [
                    'type' => 'current',
                    'description' => 'Base installation version'
                ],
                'requires_migration' => false,
            ]
        );

        // Ensure only one version is marked as current
        PanelVersion::where('version', '!=', '1.3.0')
            ->update(['is_current' => false]);
    }

    /**
     * Seed default update system settings
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            // User configurable settings
            [
                'key' => UpdateSetting::AUTO_CHECK_ENABLED,
                'value' => true,
                'description' => 'Enable automatic checking for updates',
                'is_system' => false,
            ],
            [
                'key' => UpdateSetting::CHECK_INTERVAL_HOURS,
                'value' => 24,
                'description' => 'Hours between automatic update checks',
                'is_system' => false,
            ],
            [
                'key' => UpdateSetting::AUTO_BACKUP_ENABLED,
                'value' => true,
                'description' => 'Create automatic backups before updates',
                'is_system' => false,
            ],
            [
                'key' => UpdateSetting::BACKUP_RETENTION_DAYS,
                'value' => 30,
                'description' => 'Days to retain automatic backups',
                'is_system' => false,
            ],
            [
                'key' => UpdateSetting::MAX_BACKUP_SIZE_GB,
                'value' => 5,
                'description' => 'Maximum backup size in GB',
                'is_system' => false,
            ],
            [
                'key' => UpdateSetting::REQUIRE_CONFIRMATION,
                'value' => true,
                'description' => 'Require admin confirmation before updates',
                'is_system' => false,
            ],
            [
                'key' => UpdateSetting::ALLOW_BETA_UPDATES,
                'value' => false,
                'description' => 'Allow installation of beta/pre-release updates',
                'is_system' => false,
            ],
            [
                'key' => UpdateSetting::NOTIFICATION_ENABLED,
                'value' => true,
                'description' => 'Send notifications about update status',
                'is_system' => false,
            ],

            // System settings
            [
                'key' => UpdateSetting::PARALLEL_FILE_UPDATES,
                'value' => 10,
                'description' => 'Number of files to update in parallel',
                'is_system' => true,
            ],
            [
                'key' => UpdateSetting::EXCLUDED_FILE_PATTERNS,
                'value' => [
                    'storage/logs/*',
                    'storage/framework/cache/*',
                    '.env*',
                    'node_modules/*'
                ],
                'description' => 'File patterns to exclude from updates',
                'is_system' => true,
            ],
            [
                'key' => UpdateSetting::CRITICAL_FILES,
                'value' => [
                    'config/app.php',
                    'config/database.php',
                    'composer.json'
                ],
                'description' => 'Critical files that require special handling',
                'is_system' => true,
            ],
            [
                'key' => UpdateSetting::GITHUB_CONFIG,
                'value' => [
                    'owner' => 'Owen-C137',
                    'repo' => 'Raptor-Panel',
                    'branch' => 'main',
                    'api_base' => 'https://api.github.com/repos/Owen-C137/Raptor-Panel',
                    'raw_base' => 'https://raw.githubusercontent.com/Owen-C137/Raptor-Panel/main'
                ],
                'description' => 'GitHub repository configuration',
                'is_system' => true,
            ],
            [
                'key' => UpdateSetting::TEMP_DIRECTORY,
                'value' => 'storage/app/updates/temp',
                'description' => 'Temporary directory for update files',
                'is_system' => true,
            ],
            [
                'key' => UpdateSetting::BACKUP_DIRECTORY,
                'value' => 'storage/app/updates/backups',
                'description' => 'Directory for storing backups',
                'is_system' => true,
            ],
        ];

        foreach ($settings as $setting) {
            UpdateSetting::updateOrCreate(
                ['setting_key' => $setting['key']],
                [
                    'setting_value' => $setting['value'],
                    'description' => $setting['description'],
                    'is_system' => $setting['is_system'],
                ]
            );
        }
    }
}
