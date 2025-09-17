<?php

namespace PterodactylAddons\OllamaAi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Pterodactyl\Models\User;

class AiCodeTemplate extends Model
{
    protected $table = 'ai_code_templates';
    
    protected $fillable = [
        'name',
        'category',
        'description',
        'template_code',
        'parameters',
        'language',
        'is_active',
        'created_by',
        'usage_count',
    ];
    
    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function generations(): HasMany
    {
        return $this->hasMany(AiCodeGeneration::class, 'template_id');
    }
    
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
    
    public function getParameterNames(): array
    {
        return array_keys($this->parameters ?? []);
    }
    
    public function getFormattedCategory(): string
    {
        return ucwords(str_replace('_', ' ', $this->category));
    }
    
    public function isPopular(): bool
    {
        return $this->usage_count >= 10;
    }
}