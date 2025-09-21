<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Seeds initial data for the update system including:
     * - Current panel version (1.3.0)
     * - Default system settings
     */
    public function up(): void
    {
        // Insert current panel version
        DB::table('panel_versions')->insert([
            'version' => '1.3.0',
            'is_current' => true,
            'release_date' => now(),
            'release_notes' => 'Current installed version of Raptor Panel',
            'changelog_data' => json_encode([
                'type' => 'current',
                'description' => 'Base installation version'
            ]),
            'requires_migration' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Insert default update system settings
        $defaultSettings = [
            [
                'setting_key' => 'auto_check_enabled',
                'setting_value' => json_encode(true),
                'description' => 'Enable automatic checking for updates',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'check_interval_hours',
                'setting_value' => json_encode(24),
                'description' => 'Hours between automatic update checks',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'auto_backup_enabled',
                'setting_value' => json_encode(true),
                'description' => 'Create automatic backups before updates',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'backup_retention_days',
                'setting_value' => json_encode(30),
                'description' => 'Days to retain automatic backups',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'max_backup_size_gb',
                'setting_value' => json_encode(5),
                'description' => 'Maximum backup size in GB',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'require_confirmation',
                'setting_value' => json_encode(true),
                'description' => 'Require admin confirmation before updates',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'allow_beta_updates',
                'setting_value' => json_encode(false),
                'description' => 'Allow installation of beta/pre-release updates',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'notification_enabled',
                'setting_value' => json_encode(true),
                'description' => 'Send notifications about update status',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'parallel_file_updates',
                'setting_value' => json_encode(10),
                'description' => 'Number of files to update in parallel',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'excluded_file_patterns',
                'setting_value' => json_encode([
                    'storage/logs/*',
                    'storage/framework/cache/*',
                    '.env*',
                    'node_modules/*'
                ]),
                'description' => 'File patterns to exclude from updates',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'critical_files',
                'setting_value' => json_encode([
                    'config/app.php',
                    'config/database.php',
                    'composer.json'
                ]),
                'description' => 'Critical files that require special handling',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'github_config',
                'setting_value' => json_encode([
                    'owner' => 'Owen-C137',
                    'repo' => 'Raptor-Panel',
                    'branch' => 'main',
                    'api_base' => 'https://api.github.com/repos/Owen-C137/Raptor-Panel',
                    'raw_base' => 'https://raw.githubusercontent.com/Owen-C137/Raptor-Panel/main'
                ]),
                'description' => 'GitHub repository configuration',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'temp_directory',
                'setting_value' => json_encode('storage/app/updates/temp'),
                'description' => 'Temporary directory for update files',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'backup_directory',
                'setting_value' => json_encode('storage/app/updates/backups'),
                'description' => 'Directory for storing backups',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('update_settings')->insert($defaultSettings);
    }

    /**
     * Reverse the migrations.
     * 
     * Removes all seeded data
     */
    public function down(): void
    {
        // Remove all settings
        DB::table('update_settings')->truncate();
        
        // Remove current version entry
        DB::table('panel_versions')->where('version', '1.3.0')->delete();
    }
};
