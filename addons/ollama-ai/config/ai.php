<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Addon Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Ollama AI addon.
    | All AI processing happens locally for complete privacy.
    |
    */

    'enabled' => env('AI_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Ollama Connection Settings
    |--------------------------------------------------------------------------
    |
    | Configure connection to your local Ollama instance.
    | Default settings work with standard Ollama installation.
    |
    */
    'ollama' => [
        'host' => env('OLLAMA_HOST', 'localhost'),
        'port' => env('OLLAMA_PORT', 11434),
        'timeout' => env('OLLAMA_TIMEOUT', 30),
        'verify_ssl' => env('OLLAMA_VERIFY_SSL', false),
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        
        // Model parameters
        'temperature' => env('AI_TEMPERATURE', 0.8),
        'top_p' => env('AI_TOP_P', 0.9),
        'max_tokens' => env('AI_MAX_TOKENS', 2048),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Model Configuration
    |--------------------------------------------------------------------------
    |
    | Define which models to use for different AI tasks.
    | Models must be available in your local Ollama instance.
    |
    */
    'models' => [
        'default' => env('AI_DEFAULT_MODEL', 'llama3.1:8b'),
        'chat' => env('AI_CHAT_MODEL', 'llama3.1:8b'),
        'code' => env('AI_CODE_MODEL', 'codellama:7b'),
        'analysis' => env('AI_ANALYSIS_MODEL', 'mistral:7b'),
        'security' => env('AI_SECURITY_MODEL', 'llama3.1:8b'),
        'documentation' => env('AI_DOCS_MODEL', 'gemma:7b'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific AI features.
    | Useful for gradual rollout or resource management.
    |
    */
    'features' => [
        'chat_support' => env('AI_CHAT_ENABLED', true),
        'server_analysis' => env('AI_ANALYSIS_ENABLED', true),
        'performance_monitoring' => env('AI_MONITORING_ENABLED', true),
        'security_scanning' => env('AI_SECURITY_ENABLED', true),
        'code_generation' => env('AI_CODE_ENABLED', true),
        'predictive_analytics' => env('AI_PREDICTION_ENABLED', true),
        'admin_insights' => env('AI_ADMIN_INSIGHTS_ENABLED', true),
        'user_assistance' => env('AI_USER_HELP_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance & Limits
    |--------------------------------------------------------------------------
    |
    | Configure resource limits and performance settings.
    | Adjust based on your server capabilities.
    |
    */
    'limits' => [
        'max_chat_history' => env('AI_MAX_CHAT_HISTORY', 50),
        'analysis_interval' => env('AI_ANALYSIS_INTERVAL', 300), // seconds
        'max_concurrent_requests' => env('AI_MAX_CONCURRENT', 10),
        'request_timeout' => env('AI_REQUEST_TIMEOUT', 60), // seconds
        'max_tokens' => env('AI_MAX_TOKENS', 2048),
        'context_window' => env('AI_CONTEXT_WINDOW', 4096),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Configure how long to keep AI-generated data.
    | Set to null for indefinite retention.
    |
    */
    'retention' => [
        'conversations' => env('AI_CONVERSATION_RETENTION_DAYS', 30),
        'analysis_results' => env('AI_ANALYSIS_RETENTION_DAYS', 90),
        'insights' => env('AI_INSIGHTS_RETENTION_DAYS', 60),
        'logs' => env('AI_LOGS_RETENTION_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy & Security
    |--------------------------------------------------------------------------
    |
    | Configure privacy and security settings.
    | All processing remains local by default.
    |
    */
    'privacy' => [
        'anonymize_data' => env('AI_ANONYMIZE_DATA', true),
        'encrypt_conversations' => env('AI_ENCRYPT_CONVERSATIONS', true),
        'audit_ai_actions' => env('AI_AUDIT_ENABLED', true),
        'require_user_consent' => env('AI_REQUIRE_CONSENT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure AI-specific logging settings.
    | Separate from main Pterodactyl logs for easier management.
    |
    */
    'logging' => [
        'enabled' => env('AI_LOGGING_ENABLED', true),
        'level' => env('AI_LOG_LEVEL', 'info'),
        'channel' => env('AI_LOG_CHANNEL', 'daily'),
        'path' => env('AI_LOG_PATH', storage_path('logs/ai')),
    ],
];