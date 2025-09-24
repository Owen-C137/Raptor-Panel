<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Raptor Panel Update Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Raptor Panel
    | automatic update system, including GitHub integration, backup
    | settings, validation rules, and safety measures.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | GitHub Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for GitHub API integration to fetch releases and
    | download update packages.
    |
    */
    'github' => [
        'owner' => env('GITHUB_OWNER', 'RaptorPanel'),
        'repo' => env('GITHUB_REPO', 'raptor-panel'),
        'api_base' => 'https://api.github.com',
        'api_token' => env('GITHUB_TOKEN', env('GITHUB_API_TOKEN', null)), // Support both GITHUB_TOKEN and GITHUB_API_TOKEN
        'timeout' => 30,
        'user_agent' => 'Raptor-Panel-Updater/1.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | System Requirements
    |--------------------------------------------------------------------------
    |
    | Minimum system requirements for running updates safely.
    |
    */
    'requirements' => [
        'min_php_version' => '8.1.0',
        'min_mysql_version' => '8.0',
        'min_memory_limit' => '256M',
        'min_free_space' => 1073741824, // 1GB in bytes
        'min_update_space' => 536870912, // 512MB in bytes
        'required_extensions' => [
            'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash',
            'mbstring', 'openssl', 'pcre', 'pdo', 'pdo_mysql', 'session',
            'tokenizer', 'xml', 'zip'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for system backups created before updates.
    |
    */
    'backup' => [
        'enabled' => true,
        'path' => storage_path('app/backups/updates'),
        'retention_days' => 30,
        'max_backups' => 10,
        'backup_storage' => true, // Include storage files in backup
        'exclude_paths' => [
            'storage/logs',
            'storage/app/backups',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'node_modules',
            '.git',
            'vendor'
        ],
        'compression_level' => 6, // ZIP compression level (0-9)
        'verify_backup' => true, // Verify backup integrity after creation
    ],

    /*
    |--------------------------------------------------------------------------
    | File Update Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for file operations during updates.
    |
    */
    'files' => [
        'temp_dir' => sys_get_temp_dir() . '/raptor_updates',
        'backup_changed_files' => true,
        'verify_checksums' => true,
        'preserve_permissions' => true,
        'default_file_permissions' => 0644,
        'default_dir_permissions' => 0755,
        'max_file_size' => 104857600, // 100MB max file size
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Migration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for database migrations during updates.
    |
    */
    'migrations' => [
        'backup_before_migration' => true,
        'timeout' => 300, // 5 minutes
        'chunk_size' => 1000, // For large data migrations
        'verify_integrity' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Progress Tracking
    |--------------------------------------------------------------------------
    |
    | Settings for real-time progress tracking and reporting.
    |
    */
    'progress' => [
        'save_interval' => 2, // Seconds between progress saves
        'detailed_logging' => true,
        'max_log_entries' => 1000,
        'websocket_updates' => false, // Enable for real-time updates
        'websocket_channel' => 'update-progress',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Configuration for pre and post-update validation.
    |
    */
    'validation' => [
        'pre_update' => [
            'system_requirements' => true,
            'disk_space' => true,
            'database_connectivity' => true,
            'file_permissions' => true,
            'application_state' => true,
            'backup_prerequisites' => true,
            'network_connectivity' => false, // Optional
        ],
        'post_update' => [
            'application_health' => true,
            'database_integrity' => true,
            'file_integrity' => true,
            'configuration_validity' => true,
            'service_availability' => true,
            'performance_check' => false, // Optional
        ],
        'strict_mode' => false, // Fail on warnings if true
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety and Recovery
    |--------------------------------------------------------------------------
    |
    | Safety measures and recovery options.
    |
    */
    'safety' => [
        'auto_rollback' => true, // Automatically rollback on failure
        'rollback_timeout' => 600, // 10 minutes for rollback operations
        'maintenance_mode' => true, // Put app in maintenance during update
        'stop_queues' => true, // Stop queue workers during update
        'max_update_time' => 1800, // 30 minutes max update time
        'confirm_destructive_changes' => true,
        'require_manual_confirmation' => false, // For critical updates
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Notification settings for update events.
    |
    */
    'notifications' => [
        'enabled' => true,
        'channels' => ['database', 'log'], // mail, slack, discord
        'events' => [
            'update_available' => true,
            'update_started' => true,
            'update_completed' => true,
            'update_failed' => true,
            'rollback_completed' => true,
        ],
        'recipients' => [
            // 'admin@example.com'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    |
    | Automatic update checking and scheduling.
    |
    */
    'scheduling' => [
        'auto_check' => true,
        'check_frequency' => 'daily', // daily, weekly, monthly
        'auto_update' => false, // Dangerous - only for development
        'auto_update_types' => ['patch'], // patch, minor, major
        'maintenance_window' => [
            'enabled' => false,
            'timezone' => 'UTC',
            'days' => ['sunday'], // monday, tuesday, etc.
            'start_time' => '02:00',
            'end_time' => '06:00',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Detailed logging configuration for update operations.
    |
    */
    'logging' => [
        'level' => env('UPDATE_LOG_LEVEL', 'info'), // debug, info, warning, error
        'channel' => 'updates', // Laravel log channel
        'separate_file' => true,
        'max_files' => 10,
        'include_context' => true,
        'log_sql_queries' => false, // Log database queries during updates
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance optimization settings for large updates.
    |
    */
    'performance' => [
        'memory_limit' => '512M', // Memory limit for update process
        'time_limit' => 0, // No time limit for update process
        'chunk_processing' => true,
        'chunk_size' => 100, // Files processed per chunk
        'parallel_processing' => false, // Experimental feature
        'optimize_after_update' => true, // Run optimization commands
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments.
    |
    */
    'development' => [
        'mock_github_api' => env('MOCK_GITHUB_API', false),
        'simulate_failures' => env('SIMULATE_UPDATE_FAILURES', false),
        'skip_validations' => env('SKIP_UPDATE_VALIDATIONS', false),
        'preserve_test_data' => env('PRESERVE_TEST_DATA', false),
        'debug_mode' => env('UPDATE_DEBUG_MODE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific update features.
    |
    */
    'features' => [
        'incremental_updates' => true,
        'delta_downloads' => false, // Download only changed files
        'background_updates' => false, // Update in background
        'update_preview' => true, // Preview changes before applying
        'rollback_points' => true, // Create rollback points during update
        'health_monitoring' => true, // Monitor system health during update
    ],
];