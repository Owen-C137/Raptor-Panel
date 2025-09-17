<?php

namespace PterodactylAddons\OllamaAi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Models\User;

class AiCodeGeneration extends Model
{
    protected $table = 'ai_code_generations';
    
    protected $fillable = [
        'user_id',
        'type',
        'parameters',
        'generated_code',
        'documentation',
        'validation_results',
        'context_data',
        'ai_confidence',
        'template_id',
        'is_successful',
    ];
    
    protected $casts = [
        'parameters' => 'array',
        'validation_results' => 'array',
        'context_data' => 'array',
        'ai_confidence' => 'float',
        'is_successful' => 'boolean',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function template(): BelongsTo
    {
        return $this->belongsTo(AiCodeTemplate::class, 'template_id');
    }
    
    public function isValid(): bool
    {
        return $this->validation_results['is_valid'] ?? false;
    }
    
    public function hasSecurityIssues(): bool
    {
        return !empty($this->validation_results['security_issues']);
    }
    
    public function getSecurityIssues(): array
    {
        return $this->validation_results['security_issues'] ?? [];
    }
    
    public function getPerformanceWarnings(): array
    {
        return $this->validation_results['performance_warnings'] ?? [];
    }
    
    public function getSuggestions(): array
    {
        return $this->validation_results['suggestions'] ?? [];
    }
    
    public function getFormattedType(): string
    {
        return ucwords(str_replace('_', ' ', $this->type));
    }
}