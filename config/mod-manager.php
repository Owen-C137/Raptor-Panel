<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mod Manager Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Mod Manager addon, including API settings,
    | harvesting strategies, and performance tuning options.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | CurseForge API Configuration
    |--------------------------------------------------------------------------
    */
    'curseforge' => [
        'api_key' => env('CURSEFORGE_API_KEY', ''),
        'base_url' => 'https://api.curseforge.com/v1',
        'rate_limit' => [
            'calls_per_second' => 1,
            'burst_limit' => 5,
            'backoff_multiplier' => 2,
        ],
        'timeout' => 30,
        'retry_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Harvesting Configuration
    |--------------------------------------------------------------------------
    */
    'harvesting' => [
        'strategy' => 'smart_simultaneous',
        'max_concurrent_jobs' => 20,
        'category_batch_size' => 5,
        'rate_limit_delay' => 1,
        'queue_name' => 'mod-harvest',
        'retry_attempts' => 3,
        'timeout_minutes' => 90,
        
        'job_priorities' => [
            'popular' => 10,
            'category' => 8,
            'files' => 6,
            'maintenance' => 2,
        ],
        
        'memory_limit' => '2G',
        'chunk_size' => 100,
        'api_burst_limit' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Game Configuration
    |--------------------------------------------------------------------------
    */
    'games' => [
        'default' => 'minecraft',
        'enabled' => [
            'minecraft' => [
                'id' => 432,
                'name' => 'Minecraft',
                'slug' => 'minecraft',
            ],
            // Future games can be added here
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'batch_size' => 1000,
        'index_optimization' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'driver' => env('CACHE_DRIVER', 'redis'),
        'prefix' => 'mod_manager',
        'ttl' => [
            'api_responses' => 3600, // 1 hour
            'mod_data' => 86400,     // 24 hours
            'categories' => 604800,  // 1 week
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File System Configuration
    |--------------------------------------------------------------------------
    */
    'filesystem' => [
        'temp_path' => storage_path('app/mod-manager/temp'),
        'backup_path' => storage_path('app/mod-manager/backups'),
        'max_file_size' => '500M',
        'allowed_extensions' => ['.jar', '.zip', '.7z', '.rar'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    'security' => [
        'validate_checksums' => true,
        'scan_for_viruses' => false,
        'quarantine_suspicious' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Progress Tracking
    |--------------------------------------------------------------------------
    */
    'progress' => [
        'update_interval' => 5,  // seconds
        'broadcast_channel' => 'mod-manager',
        'persist_history' => true,
    ],
];