<?php

/**
 * Bootstrap file for Ollama AI Addon
 * 
 * This file provides initial registration for the addon so that
 * the install command can be discovered and run.
 */

// Ensure this is being run from the correct context
if (!defined('LARAVEL_START')) {
    return;
}

// Auto-register the addon if it hasn't been registered yet
$registrationFile = base_path('.pterodactyl/addons/ollama-ai.json');

if (!file_exists($registrationFile)) {
    // Create registration directory
    $registrationDir = dirname($registrationFile);
    if (!is_dir($registrationDir)) {
        mkdir($registrationDir, 0755, true);
    }
    
    // Create registration file
    $registration = [
        'name' => 'Ollama AI',
        'version' => '1.5.0',
        'namespace' => 'PterodactylAddons\\OllamaAi\\',
        'path' => 'addons/ollama-ai/src/',
        'service_provider' => 'PterodactylAddons\\OllamaAi\\AiServiceProvider',
        'installed_at' => date('Y-m-d H:i:s'),
        'auto_discovery' => true
    ];
    
    file_put_contents($registrationFile, json_encode($registration, JSON_PRETTY_PRINT));
}

// Register in composer autoload if needed
$composerPath = base_path('composer.json');
if (file_exists($composerPath)) {
    $composer = json_decode(file_get_contents($composerPath), true);
    
    if (!isset($composer['autoload']['psr-4']['PterodactylAddons\\OllamaAi\\'])) {
        $composer['autoload']['psr-4']['PterodactylAddons\\OllamaAi\\'] = 'addons/ollama-ai/src/';
        file_put_contents($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        // Regenerate autoload
        shell_exec('cd ' . base_path() . ' && composer dump-autoload 2>/dev/null');
    }
}

// Register the service provider
if (class_exists('Illuminate\\Foundation\\Application')) {
    $app = app();
    
    try {
        if (!$app->providerIsLoaded('PterodactylAddons\\OllamaAi\\AiServiceProvider')) {
            $app->register('PterodactylAddons\\OllamaAi\\AiServiceProvider');
        }
    } catch (\Exception $e) {
        // Service provider registration failed, but continue silently
        // This allows the install command to handle it properly
    }
}