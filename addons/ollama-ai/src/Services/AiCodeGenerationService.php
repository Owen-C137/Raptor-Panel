<?php

namespace PterodactylAddons\OllamaAi\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use PterodactylAddons\OllamaAi\Models\AiCodeGeneration;
use PterodactylAddons\OllamaAi\Models\AiCodeTemplate;
use PterodactylAddons\OllamaAi\Services\AiAnalyticsService;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;

class AiCodeGenerationService
{
    protected $ollamaService;
    protected $aiAnalyticsService;

    public function __construct(OllamaService $ollamaService, AiAnalyticsService $aiAnalyticsService)
    {
        $this->ollamaService = $ollamaService;
        $this->aiAnalyticsService = $aiAnalyticsService;
    }

    /**
     * Generate code based on user request and context
     */
    public function generateCode(string $type, array $parameters, array $context = []): array
    {
        $user = Auth::user();
        
        // Validate generation type
        $this->validateGenerationType($type);
        
        // Get appropriate template
        $template = $this->getTemplate($type, $parameters);
        
        // Enhance context with user-specific information
        $enhancedContext = $this->enhanceContext($context, $user);
        
        // Generate code using AI
        $generatedCode = $this->performCodeGeneration($type, $parameters, $template, $enhancedContext);
        
        // Post-process and validate generated code
        $processedCode = $this->postProcessCode($generatedCode, $type, $parameters);
        
        // Store generation record
        $generation = $this->storeGeneration($user, $type, $parameters, $processedCode, $context);
        
        return [
            'id' => $generation->id,
            'type' => $type,
            'code' => $processedCode['code'],
            'documentation' => $processedCode['documentation'],
            'suggestions' => $processedCode['suggestions'],
            'validation_results' => $processedCode['validation'],
            'template_used' => $template ? $template->name : null,
            'ai_confidence' => $generatedCode['confidence'] ?? 0.8,
            'generated_at' => $generation->created_at,
        ];
    }

    /**
     * Generate configuration files for servers
     */
    public function generateServerConfiguration(array $serverSpecs, array $options = []): array
    {
        $configurationType = $options['type'] ?? 'general';
        $gameType = $serverSpecs['game'] ?? 'minecraft';
        
        $context = array_merge([
            'server_specs' => $serverSpecs,
            'configuration_type' => $configurationType,
            'game_type' => $gameType,
            'optimization_level' => $options['optimization_level'] ?? 'balanced',
        ], $options);

        return $this->generateCode('server_configuration', [
            'game' => $gameType,
            'ram' => $serverSpecs['ram'] ?? '2G',
            'cpu' => $serverSpecs['cpu'] ?? 2,
            'players' => $serverSpecs['max_players'] ?? 20,
            'type' => $configurationType,
        ], $context);
    }

    /**
     * Generate automation scripts
     */
    public function generateAutomationScript(string $scriptType, array $parameters): array
    {
        $validScriptTypes = [
            'backup_automation',
            'performance_monitoring', 
            'log_rotation',
            'resource_cleanup',
            'security_hardening',
            'update_automation',
            'health_checks',
        ];

        if (!in_array($scriptType, $validScriptTypes)) {
            throw new \InvalidArgumentException("Invalid script type: {$scriptType}");
        }

        return $this->generateCode('automation_script', array_merge([
            'script_type' => $scriptType,
            'language' => $parameters['language'] ?? 'bash',
            'schedule' => $parameters['schedule'] ?? 'daily',
        ], $parameters));
    }

    /**
     * Generate Docker configurations
     */
    public function generateDockerConfiguration(array $specifications): array
    {
        return $this->generateCode('docker_configuration', [
            'base_image' => $specifications['base_image'] ?? 'ubuntu:20.04',
            'services' => $specifications['services'] ?? [],
            'ports' => $specifications['ports'] ?? [],
            'volumes' => $specifications['volumes'] ?? [],
            'environment' => $specifications['environment'] ?? [],
            'optimization' => $specifications['optimization'] ?? true,
        ]);
    }

    /**
     * Generate startup scripts with AI optimization
     */
    public function generateStartupScript(array $serverInfo, array $options = []): array
    {
        return $this->generateCode('startup_script', array_merge([
            'server_type' => $serverInfo['type'] ?? 'minecraft',
            'java_version' => $serverInfo['java_version'] ?? '17',
            'memory_allocation' => $serverInfo['memory'] ?? '2G',
            'gc_optimization' => $options['gc_optimization'] ?? true,
            'performance_flags' => $options['performance_flags'] ?? true,
            'monitoring' => $options['monitoring'] ?? false,
        ], $options));
    }

    /**
     * Generate API integration code
     */
    public function generateApiIntegration(string $apiType, array $configuration): array
    {
        return $this->generateCode('api_integration', [
            'api_type' => $apiType,
            'language' => $configuration['language'] ?? 'php',
            'authentication' => $configuration['authentication'] ?? 'bearer',
            'endpoints' => $configuration['endpoints'] ?? [],
            'error_handling' => $configuration['error_handling'] ?? true,
            'caching' => $configuration['caching'] ?? false,
        ]);
    }

    /**
     * Generate monitoring and alerting configurations
     */
    public function generateMonitoringConfig(array $monitoringSpecs): array
    {
        return $this->generateCode('monitoring_config', [
            'monitoring_type' => $monitoringSpecs['type'] ?? 'prometheus',
            'metrics' => $monitoringSpecs['metrics'] ?? [],
            'alerts' => $monitoringSpecs['alerts'] ?? [],
            'dashboards' => $monitoringSpecs['dashboards'] ?? [],
            'retention_period' => $monitoringSpecs['retention'] ?? '30d',
        ]);
    }

    /**
     * Generate database migration/setup scripts
     */
    public function generateDatabaseScript(string $dbType, array $schema): array
    {
        return $this->generateCode('database_script', [
            'database_type' => $dbType,
            'tables' => $schema['tables'] ?? [],
            'indexes' => $schema['indexes'] ?? [],
            'constraints' => $schema['constraints'] ?? [],
            'initial_data' => $schema['initial_data'] ?? [],
            'optimization' => $schema['optimization'] ?? true,
        ]);
    }

    /**
     * Get intelligent code suggestions based on context
     */
    public function getCodeSuggestions(string $currentCode, string $language, array $context = []): array
    {
        try {
            $prompt = $this->buildSuggestionsPrompt($currentCode, $language, $context);
            
            $response = $this->ollamaService->generateResponse(
                $prompt,
                'codellama:7b',
                [
                    'temperature' => 0.2,
                    'max_tokens' => 800,
                ]
            );

            return [
                'suggestions' => $this->parseSuggestions($response['response'] ?? ''),
                'improvements' => $this->parseImprovements($response['response'] ?? ''),
                'security_notes' => $this->parseSecurityNotes($response['response'] ?? ''),
                'performance_tips' => $this->parsePerformanceTips($response['response'] ?? ''),
                'best_practices' => $this->parseBestPractices($response['response'] ?? ''),
                'confidence' => $response['confidence'] ?? 0.7,
            ];
        } catch (\Exception $e) {
            return [
                'suggestions' => [],
                'improvements' => [],
                'security_notes' => [],
                'performance_tips' => [],
                'best_practices' => [],
                'error' => $e->getMessage(),
                'confidence' => 0.0,
            ];
        }
    }

    /**
     * Validate generated code
     */
    public function validateCode(string $code, string $type, array $parameters = []): array
    {
        $validation = [
            'is_valid' => true,
            'syntax_errors' => [],
            'security_issues' => [],
            'performance_warnings' => [],
            'best_practice_violations' => [],
            'suggestions' => [],
        ];

        // Perform basic syntax validation based on type
        $syntaxValidation = $this->validateSyntax($code, $type);
        $validation['syntax_errors'] = $syntaxValidation['errors'];
        $validation['is_valid'] = empty($syntaxValidation['errors']);

        // Check for security issues
        $securityCheck = $this->checkSecurity($code, $type);
        $validation['security_issues'] = $securityCheck['issues'];

        // Performance analysis
        $performanceCheck = $this->checkPerformance($code, $type, $parameters);
        $validation['performance_warnings'] = $performanceCheck['warnings'];

        // Best practices check
        $bestPracticesCheck = $this->checkBestPractices($code, $type);
        $validation['best_practice_violations'] = $bestPracticesCheck['violations'];
        $validation['suggestions'] = $bestPracticesCheck['suggestions'];

        return $validation;
    }

    /**
     * Get available code templates
     */
    public function getAvailableTemplates(string $category = null): Collection
    {
        $query = AiCodeTemplate::where('is_active', true);
        
        if ($category) {
            $query->where('category', $category);
        }
        
        return $query->orderBy('name')->get();
    }

    /**
     * Create or update code template
     */
    public function saveTemplate(array $templateData): AiCodeTemplate
    {
        return AiCodeTemplate::updateOrCreate(
            ['name' => $templateData['name']],
            [
                'category' => $templateData['category'],
                'description' => $templateData['description'],
                'template_code' => $templateData['template_code'],
                'parameters' => $templateData['parameters'] ?? [],
                'language' => $templateData['language'] ?? 'text',
                'is_active' => $templateData['is_active'] ?? true,
                'created_by' => Auth::id(),
            ]
        );
    }

    /**
     * Get generation history for user
     */
    public function getGenerationHistory(User $user, int $limit = 50): Collection
    {
        return AiCodeGeneration::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular generation types and patterns
     */
    public function getGenerationAnalytics(): array
    {
        return Cache::remember('ai_code_generation_analytics', 3600, function () {
            return [
                'popular_types' => AiCodeGeneration::selectRaw('type, count(*) as count')
                    ->groupBy('type')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'daily_generations' => AiCodeGeneration::selectRaw('DATE(created_at) as date, count(*) as count')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'success_rate' => AiCodeGeneration::where('validation_results->is_valid', true)
                    ->count() / max(AiCodeGeneration::count(), 1) * 100,
                'top_users' => AiCodeGeneration::selectRaw('user_id, count(*) as generations')
                    ->groupBy('user_id')
                    ->orderBy('generations', 'desc')
                    ->limit(10)
                    ->with('user')
                    ->get(),
            ];
        });
    }

    /**
     * Protected helper methods
     */
    protected function validateGenerationType(string $type): void
    {
        $validTypes = [
            'server_configuration',
            'automation_script', 
            'docker_configuration',
            'startup_script',
            'api_integration',
            'monitoring_config',
            'database_script',
            'security_config',
            'performance_optimization',
            'custom_script',
        ];

        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid generation type: {$type}");
        }
    }

    protected function getTemplate(string $type, array $parameters): ?AiCodeTemplate
    {
        return AiCodeTemplate::where('category', $type)
            ->where('is_active', true)
            ->first();
    }

    protected function enhanceContext(array $context, User $user): array
    {
        $userServers = Server::where('owner_id', $user->id)->get(['id', 'name', 'memory', 'disk', 'cpu']);
        
        return array_merge($context, [
            'user_id' => $user->id,
            'user_servers_count' => $userServers->count(),
            'user_experience_level' => $this->getUserExperienceLevel($user),
            'user_servers' => $userServers->toArray(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    protected function performCodeGeneration(string $type, array $parameters, ?AiCodeTemplate $template, array $context): array
    {
        $prompt = $this->buildGenerationPrompt($type, $parameters, $template, $context);
        
        try {
            $response = $this->ollamaService->generateResponse(
                $prompt,
                'codellama:7b',
                [
                    'temperature' => 0.1, // Low temperature for consistent code generation
                    'max_tokens' => 2000,
                ]
            );

            return [
                'raw_response' => $response['response'] ?? '',
                'confidence' => $response['confidence'] ?? 0.8,
                'model_used' => 'codellama:7b',
                'prompt_used' => $prompt,
            ];
        } catch (\Exception $e) {
            throw new \Exception("Code generation failed: " . $e->getMessage());
        }
    }

    protected function buildGenerationPrompt(string $type, array $parameters, ?AiCodeTemplate $template, array $context): string
    {
        $prompt = "You are an expert system administrator and developer. Generate high-quality, secure, and optimized code.\n\n";
        
        $prompt .= "Task: Generate {$type}\n";
        $prompt .= "Parameters: " . json_encode($parameters, JSON_PRETTY_PRINT) . "\n";
        
        if ($template) {
            $prompt .= "Template to follow: " . $template->template_code . "\n";
        }
        
        if (!empty($context['user_experience_level'])) {
            $prompt .= "User experience level: {$context['user_experience_level']}\n";
        }
        
        $prompt .= "\nRequirements:\n";
        $prompt .= "1. Generate clean, well-documented code\n";
        $prompt .= "2. Include security best practices\n";
        $prompt .= "3. Optimize for performance\n";
        $prompt .= "4. Add appropriate error handling\n";
        $prompt .= "5. Include usage instructions\n";
        
        $prompt .= "\nPlease provide:\n";
        $prompt .= "1. The complete code\n";
        $prompt .= "2. Documentation explaining the code\n";
        $prompt .= "3. Installation/setup instructions\n";
        $prompt .= "4. Usage examples\n";
        $prompt .= "5. Any security considerations\n";

        return $prompt;
    }

    protected function buildSuggestionsPrompt(string $code, string $language, array $context): string
    {
        $prompt = "You are a code review expert. Analyze the following {$language} code and provide suggestions.\n\n";
        $prompt .= "Code to analyze:\n```{$language}\n{$code}\n```\n\n";
        
        if (!empty($context)) {
            $prompt .= "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
        
        $prompt .= "Please provide:\n";
        $prompt .= "1. Code improvement suggestions\n";
        $prompt .= "2. Security vulnerabilities (if any)\n";
        $prompt .= "3. Performance optimization opportunities\n";
        $prompt .= "4. Best practice recommendations\n";
        $prompt .= "5. Potential bugs or issues\n";

        return $prompt;
    }

    protected function postProcessCode(array $generatedCode, string $type, array $parameters): array
    {
        $response = $generatedCode['raw_response'];
        
        // Extract code blocks from response
        $code = $this->extractCodeFromResponse($response);
        $documentation = $this->extractDocumentationFromResponse($response);
        $suggestions = $this->extractSuggestionsFromResponse($response);
        
        // Validate the generated code
        $validation = $this->validateCode($code, $type, $parameters);
        
        return [
            'code' => $code,
            'documentation' => $documentation,
            'suggestions' => $suggestions,
            'validation' => $validation,
            'raw_response' => $response,
        ];
    }

    protected function extractCodeFromResponse(string $response): string
    {
        // Extract code blocks marked with ```
        preg_match_all('/```[\w]*\n?(.*?)\n?```/s', $response, $matches);
        
        if (!empty($matches[1])) {
            return trim($matches[1][0]);
        }
        
        // If no code blocks found, return the response (might be plain code)
        return trim($response);
    }

    protected function extractDocumentationFromResponse(string $response): string
    {
        // Extract documentation that's not in code blocks
        $withoutCodeBlocks = preg_replace('/```[\w]*.*?```/s', '', $response);
        return trim($withoutCodeBlocks);
    }

    protected function extractSuggestionsFromResponse(string $response): array
    {
        $suggestions = [];
        
        // Look for numbered lists or bullet points
        if (preg_match_all('/^\d+\.\s+(.+)$/m', $response, $matches)) {
            $suggestions = array_merge($suggestions, $matches[1]);
        }
        
        if (preg_match_all('/^[-*]\s+(.+)$/m', $response, $matches)) {
            $suggestions = array_merge($suggestions, $matches[1]);
        }
        
        return array_unique($suggestions);
    }

    protected function storeGeneration(User $user, string $type, array $parameters, array $processedCode, array $context): AiCodeGeneration
    {
        return AiCodeGeneration::create([
            'user_id' => $user->id,
            'type' => $type,
            'parameters' => $parameters,
            'generated_code' => $processedCode['code'],
            'documentation' => $processedCode['documentation'],
            'validation_results' => $processedCode['validation'],
            'context_data' => $context,
            'ai_confidence' => $processedCode['validation']['confidence'] ?? 0.8,
        ]);
    }

    protected function getUserExperienceLevel(User $user): string
    {
        // Simple experience assessment based on account age and server count
        $accountAge = $user->created_at->diffInDays(now());
        $serverCount = Server::where('owner_id', $user->id)->count();
        
        if ($accountAge > 365 && $serverCount > 10) {
            return 'expert';
        } elseif ($accountAge > 90 && $serverCount > 3) {
            return 'intermediate';
        } else {
            return 'beginner';
        }
    }

    protected function validateSyntax(string $code, string $type): array
    {
        $errors = [];
        
        // Basic syntax validation based on type
        switch ($type) {
            case 'startup_script':
                if (strpos($code, '#!/bin/') === false && strpos($code, '#!/usr/bin/') === false) {
                    $errors[] = 'Missing shebang line for shell script';
                }
                break;
            case 'docker_configuration':
                if (strpos($code, 'FROM ') === false) {
                    $errors[] = 'Missing FROM directive in Dockerfile';
                }
                break;
        }
        
        return ['errors' => $errors];
    }

    protected function checkSecurity(string $code, string $type): array
    {
        $issues = [];
        
        // Check for common security issues
        if (preg_match('/eval\s*\(/', $code)) {
            $issues[] = 'Use of eval() function detected - potential security risk';
        }
        
        if (preg_match('/exec\s*\(/', $code) || preg_match('/system\s*\(/', $code)) {
            $issues[] = 'Direct command execution detected - ensure proper input validation';
        }
        
        if (preg_match('/password\s*=\s*["\'][^"\']+["\']/', $code)) {
            $issues[] = 'Hardcoded password detected - use environment variables instead';
        }
        
        return ['issues' => $issues];
    }

    protected function checkPerformance(string $code, string $type, array $parameters): array
    {
        $warnings = [];
        
        // Performance checks based on type
        if ($type === 'startup_script') {
            if (!preg_match('/-Xmx\d+[GM]/', $code)) {
                $warnings[] = 'Consider setting explicit memory allocation with -Xmx flag';
            }
        }
        
        return ['warnings' => $warnings];
    }

    protected function checkBestPractices(string $code, string $type): array
    {
        $violations = [];
        $suggestions = [];
        
        // Check for documentation
        if (!preg_match('/^#/', $code) && !preg_match('/\/\*/', $code)) {
            $violations[] = 'Missing documentation/comments';
            $suggestions[] = 'Add comments explaining the code functionality';
        }
        
        // Check for error handling
        if (!preg_match('/try\s*{|catch\s*\(|if\s*\[/', $code)) {
            $suggestions[] = 'Consider adding error handling for robustness';
        }
        
        return [
            'violations' => $violations,
            'suggestions' => $suggestions,
        ];
    }

    // Additional parsing methods for suggestions
    protected function parseSuggestions(string $response): array
    {
        return $this->extractListItems($response, 'suggestions?');
    }

    protected function parseImprovements(string $response): array
    {
        return $this->extractListItems($response, 'improvements?');
    }

    protected function parseSecurityNotes(string $response): array
    {
        return $this->extractListItems($response, 'security');
    }

    protected function parsePerformanceTips(string $response): array
    {
        return $this->extractListItems($response, 'performance');
    }

    protected function parseBestPractices(string $response): array
    {
        return $this->extractListItems($response, 'best practices?');
    }

    protected function extractListItems(string $text, string $section): array
    {
        $items = [];
        
        // Look for section headers and extract following list items
        if (preg_match("/(?:^|\n)\s*(?:\d+\.\s*)?{$section}:?\s*\n(.*?)(?=\n\s*(?:\d+\.\s*)?\w+:|$)/is", $text, $sectionMatch)) {
            $sectionText = $sectionMatch[1];
            
            // Extract numbered or bulleted items
            if (preg_match_all('/(?:^|\n)\s*(?:\d+\.|-|\*)\s+(.+)/m', $sectionText, $matches)) {
                $items = array_map('trim', $matches[1]);
            }
        }
        
        return $items;
    }
}