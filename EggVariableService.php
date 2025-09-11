<?php

namespace Pterodactyl\Services\Shop;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Illuminate\Support\Str;

class EggVariableService
{
    /**
     * Default placeholder values for common variable types
     */
    protected array $defaultPlaceholders = [
        // Steam-related variables
        'STEAM_ACC' => 'placeholder000000000000000000000', // 32-character placeholder
        'STEAM_USER' => '',
        'STEAM_PASS' => '',
        'STEAM_AUTH' => '',
        
        // Game-specific variables with common defaults
        'RCON_PASS' => null, // Will generate random password
        'ARK_PASSWORD' => '',
        'WORLD_SEED' => null, // Will generate random seed
        'SERVER_IMG' => '',
        'SERVER_LOGO' => '',
        'MAP_URL' => '',
        'WORKSHOP_ID' => '',
        'ADDITIONAL_ARGS' => '',
        'ARGS' => '',
        
        // Source engine defaults
        'SRCDS_APPID' => null, // Requires specific game ID
        'SRCDS_GAME' => null, // Requires specific game name
        'SRCDS_MAP' => null, // Requires specific map
        'LUA_REFRESH' => '0',
        
        // Minecraft
        'FORGE_VERSION' => null, // Null for nullable field with regex validation
        
        // Other
        'AUTO_UPDATE' => '1',
    ];

    /**
     * Game-specific configurations for Source engine games
     */
    protected array $sourceEngineConfigs = [
        // Team Fortress 2
        10 => [
            'SRCDS_APPID' => '232250',
            'SRCDS_MAP' => 'cp_dustbowl',
        ],
        // CS:GO
        9 => [
            'SRCDS_APPID' => '740',
            'SRCDS_MAP' => 'de_dust2',
        ],
        // Garry's Mod
        7 => [
            'SRCDS_APPID' => '4000',
            'SRCDS_MAP' => 'gm_flatgrass',
        ],
    ];

    /**
     * Get required variables for an egg that need user input or placeholders
     */
    public function getRequiredVariablesForEgg(int $eggId): array
    {
        $egg = Egg::with('variables')->find($eggId);
        if (!$egg) {
            return [];
        }

        $requiredVariables = [];
        
        foreach ($egg->variables as $variable) {
            if ($variable->user_viewable && empty($variable->default_value)) {
                $requiredVariables[] = [
                    'variable' => $variable,
                    'name' => $variable->name,
                    'env_variable' => $variable->env_variable,
                    'description' => $variable->description,
                    'rules' => $variable->rules,
                    'type' => $this->determineVariableType($variable),
                    'placeholder' => $this->getPlaceholderForVariable($variable, $eggId),
                    'user_friendly_name' => $this->getUserFriendlyName($variable),
                    'help_text' => $this->getHelpText($variable),
                ];
            }
        }

        return $requiredVariables;
    }

    /**
     * Generate server variables for an order with user inputs
     */
    public function generateServerVariables(int $eggId, array $userInputs = []): array
    {
        $requiredVariables = $this->getRequiredVariablesForEgg($eggId);
        $serverVariables = [];

        foreach ($requiredVariables as $varInfo) {
            $envVar = $varInfo['env_variable'];
            $userValue = $userInputs[$envVar] ?? null;

            if (!empty($userValue)) {
                // Use user-provided value
                $serverVariables[$envVar] = $userValue;
            } else {
                // Use placeholder/default
                $serverVariables[$envVar] = $varInfo['placeholder'];
            }
        }

        return $serverVariables;
    }

    /**
     * Determine the type of variable for UI purposes
     */
    protected function determineVariableType(EggVariable $variable): string
    {
        $envVar = $variable->env_variable;
        $name = strtolower($variable->name);
        $rules = $variable->rules;

        if (str_contains($envVar, 'STEAM_ACC') || str_contains($variable->name, 'Steam Account')) {
            return 'steam_token';
        }
        
        if (str_contains($envVar, 'PASSWORD') || str_contains($envVar, 'PASS')) {
            return 'password';
        }
        
        if (str_contains($envVar, 'TOKEN') || str_contains($name, 'token')) {
            return 'token';
        }
        
        if (str_contains($rules, 'url')) {
            return 'url';
        }
        
        if (str_contains($rules, 'boolean')) {
            return 'boolean';
        }
        
        if (str_contains($rules, 'numeric') || str_contains($rules, 'integer')) {
            return 'number';
        }

        return 'text';
    }

    /**
     * Get placeholder value for a variable
     */
    protected function getPlaceholderForVariable(EggVariable $variable, int $eggId): string
    {
        $envVar = $variable->env_variable;

        // Check if we have a game-specific config
        if (isset($this->sourceEngineConfigs[$eggId][$envVar])) {
            return $this->sourceEngineConfigs[$eggId][$envVar];
        }

        // Check if we have a default placeholder
        if (isset($this->defaultPlaceholders[$envVar])) {
            $placeholder = $this->defaultPlaceholders[$envVar];
            
            // Handle special cases that need generation
            if ($placeholder === null) {
                return $this->generatePlaceholderValue($variable);
            }
            
            return $placeholder;
        }

        // Generate a placeholder based on variable type
        return $this->generatePlaceholderValue($variable);
    }

    /**
     * Generate a placeholder value for a variable
     */
    protected function generatePlaceholderValue(EggVariable $variable): string
    {
        $rules = $variable->rules;
        $envVar = $variable->env_variable;

        if (str_contains($envVar, 'RCON_PASS') || str_contains($envVar, 'PASSWORD')) {
            return Str::random(12);
        }

        if (str_contains($envVar, 'SEED')) {
            return (string) rand(100000, 999999);
        }

        // Handle FORGE_VERSION - return null for nullable with regex validation
        if ($envVar === 'FORGE_VERSION') {
            return '';  // Return empty string which should be converted to null if nullable
        }

        if (str_contains($rules, 'boolean')) {
            return '0';
        }

        if (str_contains($rules, 'numeric') || str_contains($rules, 'integer')) {
            return '0';
        }

        return '';
    }

    /**
     * Get user-friendly name for display
     */
    protected function getUserFriendlyName(EggVariable $variable): string
    {
        $envVar = $variable->env_variable;

        $friendlyNames = [
            'STEAM_ACC' => 'Steam Game Server Login Token',
            'RCON_PASS' => 'RCON Password',
            'ARK_PASSWORD' => 'Server Password',
            'WORLD_SEED' => 'World Seed',
            'SERVER_IMG' => 'Server Header Image URL',
            'SERVER_LOGO' => 'Server Logo URL',
            'MAP_URL' => 'Custom Map URL',
        ];

        return $friendlyNames[$envVar] ?? $variable->name;
    }

    /**
     * Get help text for the variable
     */
    protected function getHelpText(EggVariable $variable): string
    {
        $envVar = $variable->env_variable;

        $helpTexts = [
            'STEAM_ACC' => 'Get your token from https://steamcommunity.com/dev/managegameservers. Must be exactly 32 characters.',
            'RCON_PASS' => 'Password for remote console access. Leave empty to generate a random password.',
            'ARK_PASSWORD' => 'Password players need to join the server. Leave empty for no password.',
            'WORLD_SEED' => 'Seed for world generation. Leave empty to generate randomly.',
            'SERVER_IMG' => 'URL to header image displayed in server browser.',
            'SERVER_LOGO' => 'URL to logo image for Rust+ app.',
            'MAP_URL' => 'Direct download URL for custom map. Leave empty to use default.',
            'WORKSHOP_ID' => 'Steam Workshop collection ID (numbers from workshop URL).',
        ];

        return $helpTexts[$envVar] ?? $variable->description;
    }

    /**
     * Check if an egg requires user input for variables
     */
    public function eggRequiresUserInput(int $eggId): bool
    {
        $requiredVars = $this->getRequiredVariablesForEgg($eggId);
        
        // Only require user input for certain critical variables
        foreach ($requiredVars as $varInfo) {
            if (in_array($varInfo['type'], ['steam_token', 'password'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get variables that should be shown to users during checkout
     */
    public function getCheckoutVariables(int $eggId): array
    {
        $allRequired = $this->getRequiredVariablesForEgg($eggId);
        
        // Show important variables that users should configure at checkout
        return array_filter($allRequired, function($varInfo) {
            $envVar = $varInfo['env_variable'];
            $type = $varInfo['type'];
            
            // Include critical variables users need to set
            return in_array($type, ['steam_token', 'password']) || 
                   str_contains($envVar, 'TOKEN') ||
                   str_contains($envVar, 'BOT_') ||
                   str_contains($envVar, 'CLIENT_') ||
                   str_contains($envVar, 'DISCORD_') ||
                   str_contains($envVar, 'API_');
        });
    }
}
