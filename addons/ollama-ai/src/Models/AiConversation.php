<?php

namespace PterodactylAddons\OllamaAi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Conversation Model
 * 
 * Represents a conversation thread between a user and AI.
 * Each conversation can have multiple messages.
 */
class AiConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $fillable = [
        'user_id',
        'title',
        'context_type',
        'context_id',
        'model_used',
        'status',
        'metadata',
        'started_at',
        'last_message_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the user that owns this conversation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Pterodactyl\Models\User::class);
    }

    /**
     * Get all messages in this conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class)->orderBy('created_at');
    }

    /**
     * Get the latest message in this conversation
     */
    public function latestMessage()
    {
        return $this->hasOne(AiMessage::class)->latest();
    }

    /**
     * Scope to get active conversations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get conversations by context
     */
    public function scopeByContext($query, string $type, int $id = null)
    {
        $query = $query->where('context_type', $type);
        
        if ($id !== null) {
            $query->where('context_id', $id);
        }
        
        return $query;
    }

    /**
     * Generate a title for the conversation based on first message
     */
    public function generateTitle(): void
    {
        if ($this->title) {
            return;
        }

        $firstMessage = $this->messages()->where('role', 'user')->first();
        
        if ($firstMessage) {
            // Take first 50 characters as title
            $this->title = \Str::limit($firstMessage->content, 50);
            $this->save();
        }
    }

    /**
     * Update the last message timestamp
     */
    public function touchLastMessage(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Get conversation context for AI
     */
    public function getContextForAi(int $limit = 10): array
    {
        return $this->messages()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            })
            ->toArray();
    }

    /**
     * Archive this conversation
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Get conversation statistics
     */
    public function getStats(): array
    {
        $messageCount = $this->messages()->count();
        $userMessages = $this->messages()->where('role', 'user')->count();
        $aiMessages = $this->messages()->where('role', 'assistant')->count();
        
        return [
            'total_messages' => $messageCount,
            'user_messages' => $userMessages,
            'ai_messages' => $aiMessages,
            'duration' => $this->started_at?->diffForHumans($this->last_message_at),
            'tokens_used' => $this->messages()->sum('tokens_used'),
        ];
    }
}