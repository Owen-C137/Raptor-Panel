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
    |
    | IMPORTANT: CurseForge API Pagination Limit
    | The CurseForge API has a hard limit: (index + pageSize <= 10,000)
    | This means you can only access the first 10,000 items in any search.
    | 
    | To collect more mods beyond this limit, use:
    | 1. Category-specific searches (each category resets the 10K limit)
    | 2. Different sort orders (downloads, popularity, name, etc.)
    | 3. Filtered searches (game version, mod loader, etc.)
    |
    */
    'curseforge' => [
        'api_key' => env('CURSEFORGE_API_KEY', ''),
        'base_url' => 'https://api.curseforge.com/v1',
        'rate_limit' => [
            // You can safely raise this if your key allows faster limits.
            // Use env CURSEFORGE_CALLS_PER_SECOND to tune.
            'calls_per_second' => (int) env('CURSEFORGE_CALLS_PER_SECOND', 1),
            // Maximum tokens accumulated (burst). Higher allows short spikes.
            'burst_limit' => (int) env('CURSEFORGE_BURST_LIMIT', 5),
            'backoff_multiplier' => (int) env('CURSEFORGE_BACKOFF_MULTIPLIER', 2),
            'enabled' => (bool) env('CURSEFORGE_RATE_LIMIT_ENABLED', true),
        ],
        'timeout' => 30,
        'retry_attempts' => 3,
        'debug_logging' => (bool) env('CURSEFORGE_DEBUG_LOGGING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Harvesting Configuration
    |--------------------------------------------------------------------------
    */
    // Deprecated legacy queue harvesting config retained for reference; no longer used
    'harvesting' => [
        'deprecated' => true,
    ],


                // Harvest behavior configuration
                'harvest' => [
                    'max_pages' => env('MOD_MANAGER_MAX_PAGES', 2000), // safety upper bound
                    'popular_limit' => env('MOD_MANAGER_POPULAR_LIMIT', 1000),
                    'recent_limit' => env('MOD_MANAGER_RECENT_LIMIT', 500),
                    'sync_category_pivot' => env('MOD_MANAGER_SYNC_PIVOT', false),
                    'page_sleep_enabled' => env('MOD_MANAGER_PAGE_SLEEP', true),
                    'page_sleep_microseconds' => env('MOD_MANAGER_PAGE_SLEEP_US', 100000), // 0.1s (optimized)
                    
                    // OPTIMIZED: Enhanced file fetching strategies
                    // all    = fetch files for every processed mod (slowest, most complete)
                    // new    = fetch files only for newly created mods (balanced)
                    // batch  = collect mod IDs and fetch files after all mods are processed (faster)
                    // none   = skip file fetching entirely (can sync later)
                    'files_fetch_strategy' => env('MOD_MANAGER_FILES_FETCH', 'batch'),
                    
                    // Batch processing limits
                    'batch_size' => env('MOD_MANAGER_BATCH_SIZE', 100),
                    'files_batch_size' => env('MOD_MANAGER_FILES_BATCH', 50),
                    
                    // Smart caching
                    'skip_recent_updates' => env('MOD_MANAGER_SKIP_RECENT', true),
                    'recent_update_hours' => env('MOD_MANAGER_RECENT_HOURS', 6),
                    
                    // Performance optimizations
                    'parallel_workers' => env('MOD_MANAGER_WORKERS', 4),
                    'memory_limit' => env('MOD_MANAGER_MEMORY', '1G'),
                    'gc_frequency' => env('MOD_MANAGER_GC', 100),
                    'streaming_enabled' => env('MOD_MANAGER_STREAMING', true),
                    
                    // Batch processing rate limiting (milliseconds)
                    'batch_api_delay_ms' => env('MOD_MANAGER_BATCH_DELAY', 1200), // 1.2s between API calls
                    'batch_pause_ms' => env('MOD_MANAGER_BATCH_PAUSE', 2000),     // 2s between batches
                    
                    // When strategy new_or_stale (future), we will use these hours; reserved.
                    'stale_hours' => (int) env('MOD_MANAGER_STALE_HOURS', 24),
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
    | Cache Configuration (OPTIMIZED)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'driver' => env('CACHE_DRIVER', 'redis'),
        'prefix' => 'mod_manager',
        'ttl' => [
            // Shorter for dynamic data
            'search' => 180,         // 3 minutes
            'api_responses' => 1800, // 30 minutes  
            
            // Longer for stable data
            'mod_data' => 21600,     // 6 hours (reduced from 24)
            'file_data' => 7200,     // 2 hours
            'categories' => 604800,  // 1 week
            'games' => 2592000,      // 1 month
            
            // New cache types
            'batch_operations' => 900,   // 15 minutes
            'mod_files' => 3600,         // 1 hour
            'featured' => 1800,          // 30 minutes
        ],
        
        // Cache optimization settings
        'compression' => env('MOD_CACHE_COMPRESS', true),
        'serialization' => env('MOD_CACHE_SERIALIZE', 'igbinary'),
        'tags_enabled' => env('MOD_CACHE_TAGS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 🚀 PERFORMANCE OPTIMIZATION SETTINGS
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'batch_processing' => [
            'enabled' => env('MOD_MANAGER_BATCH_ENABLED', true),
            'chunk_size' => env('MOD_MANAGER_CHUNK_SIZE', 100),
            'memory_threshold' => env('MOD_MANAGER_MEMORY_THRESHOLD', '1.5G'),
            'gc_frequency' => env('MOD_MANAGER_GC_FREQUENCY', 50),
        ],
        'database' => [
            'bulk_insert_size' => env('MOD_MANAGER_BULK_INSERT', 100),
            'transaction_size' => env('MOD_MANAGER_TRANSACTION_SIZE', 500),
            'index_optimization' => env('MOD_MANAGER_INDEX_OPT', true),
        ],
        'category_optimization' => [
            'priority_sorting' => env('MOD_MANAGER_PRIORITY_SORT', true),
            'parallel_categories' => env('MOD_MANAGER_PARALLEL_CATS', false), // Future feature
            'smart_caching' => env('MOD_MANAGER_SMART_CACHE', true),
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | 🛡️ CIRCUIT BREAKER & RESILIENCE
    |--------------------------------------------------------------------------
    */
    'circuit_breaker' => [
        'enabled' => env('MOD_MANAGER_CIRCUIT_BREAKER', true),
        'max_failures' => env('MOD_MANAGER_CB_MAX_FAILURES', 5),
        'timeout_minutes' => env('MOD_MANAGER_CB_TIMEOUT', 5),
        'reset_timeout_minutes' => env('MOD_MANAGER_CB_RESET', 10),
    ],

    'retry' => [
        'max_attempts' => env('MOD_MANAGER_MAX_RETRIES', 3),
        'base_delay_seconds' => env('MOD_MANAGER_RETRY_DELAY', 1),
        'max_delay_seconds' => env('MOD_MANAGER_MAX_RETRY_DELAY', 30),
        'exponential_backoff' => env('MOD_MANAGER_EXP_BACKOFF', true),
        'jitter_enabled' => env('MOD_MANAGER_JITTER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 📊 API ANALYTICS & MONITORING
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'enabled' => env('MOD_MANAGER_ANALYTICS', true),
        'metrics_retention_days' => env('MOD_MANAGER_METRICS_DAYS', 30),
        'performance_tracking' => env('MOD_MANAGER_PERF_TRACK', true),
        'error_rate_threshold' => env('MOD_MANAGER_ERROR_THRESHOLD', 5.0), // 5% error rate
        'detailed_logging' => env('MOD_MANAGER_DETAILED_LOG', false),
    ],

    'api_deduplication' => [
        'enabled' => env('MOD_MANAGER_API_DEDUP', true),
        'ttl_seconds' => env('MOD_MANAGER_DEDUP_TTL', 30),
        'cache_prefix' => 'mod-manager:api-dedup',
    ],

    /*
    |--------------------------------------------------------------------------
    | 🎯 CATEGORY INTELLIGENCE
    |--------------------------------------------------------------------------
    */
    'category_intelligence' => [
        'priority_scoring' => env('MOD_MANAGER_PRIORITY_SCORING', true),
        'auto_priority_adjustment' => env('MOD_MANAGER_AUTO_PRIORITY', true),
        'popularity_weight' => env('MOD_MANAGER_POPULARITY_WEIGHT', 0.3),
        'mod_count_weight' => env('MOD_MANAGER_MOD_COUNT_WEIGHT', 0.4),
        'recency_weight' => env('MOD_MANAGER_RECENCY_WEIGHT', 0.3),
        'cache_ttl_hours' => env('MOD_MANAGER_CAT_CACHE_TTL', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | 📈 ENHANCED PROGRESS TRACKING
    |--------------------------------------------------------------------------
    */
    'progress_tracking' => [
        'real_time_updates' => env('MOD_MANAGER_REAL_TIME', true),
        'detailed_metrics' => env('MOD_MANAGER_DETAILED_METRICS', true),
        'eta_calculation' => env('MOD_MANAGER_ETA_CALC', true),
        'category_progress' => env('MOD_MANAGER_CAT_PROGRESS', true),
        'efficiency_monitoring' => env('MOD_MANAGER_EFFICIENCY', true),
        'update_frequency_seconds' => env('MOD_MANAGER_UPDATE_FREQ', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | 🔧 HARVEST OPTIMIZATION
    |--------------------------------------------------------------------------
    */
    'harvest_optimization' => [
        'smart_category_ordering' => env('MOD_MANAGER_SMART_ORDER', true),
        'skip_empty_categories' => env('MOD_MANAGER_SKIP_EMPTY', true),
        'adaptive_batch_sizing' => env('MOD_MANAGER_ADAPTIVE_BATCH', true),
        'memory_aware_processing' => env('MOD_MANAGER_MEMORY_AWARE', true),
        'predictive_caching' => env('MOD_MANAGER_PREDICTIVE_CACHE', true),
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