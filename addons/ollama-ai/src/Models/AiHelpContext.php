<?php

namespace PterodactylAddons\OllamaAi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Models\User;

class AiHelpContext extends Model
{
    protected $table = 'ai_help_contexts';
    
    protected $fillable = [
        'user_id',
        'route_name',
        'context_data',
        'help_data',
        'generated_at',
    ];
    
    protected $casts = [
        'context_data' => 'array',
        'help_data' => 'array',
        'generated_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}