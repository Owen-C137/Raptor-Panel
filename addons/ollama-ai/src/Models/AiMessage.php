<?php

namespace PterodactylAddons\OllamaAi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Message Model
 * 
 * Represents individual messages within an AI conversation.
 * Stores both user messages and AI responses.
 */
class AiMessage extends Model
{
    protected $table = 'ai_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'model_used',
        'tokens_used',
        'processing_time_ms',
        'metadata',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tokens_used' => 'integer',
        'processing_time_ms' => 'integer',
    ];

    /**
     * Message roles
     */
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_SYSTEM = 'system';

    /**
     * Message statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PROCESSING = 'processing';

    /**
     * Get the conversation this message belongs to
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class);
    }

    /**
     * Scope to get user messages
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', self::ROLE_USER);
    }

    /**
     * Scope to get AI messages
     */
    public function scopeAiMessages($query)
    {
        return $query->where('role', self::ROLE_ASSISTANT);
    }

    /**
     * Scope to get system messages
     */
    public function scopeSystemMessages($query)
    {
        return $query->where('role', self::ROLE_SYSTEM);
    }

    /**
     * Scope to get completed messages
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Check if message is from user
     */
    public function isUserMessage(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Check if message is from AI
     */
    public function isAiMessage(): bool
    {
        return $this->role === self::ROLE_ASSISTANT;
    }

    /**
     * Check if message is a system message
     */
    public function isSystemMessage(): bool
    {
        return $this->role === self::ROLE_SYSTEM;
    }

    /**
     * Mark message as completed
     */
    public function markCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Mark message as failed
     */
    public function markFailed(string $error = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($error) {
            $metadata['error'] = $error;
        }
        
        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mark message as processing
     */
    public function markProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Set processing time
     */
    public function setProcessingTime(int $milliseconds): void
    {
        $this->update(['processing_time_ms' => $milliseconds]);
    }

    /**
     * Get formatted content for display
     */
    public function getFormattedContent(): string
    {
        // Basic markdown-like formatting
        $content = $this->content;
        
        // Convert code blocks
        $content = preg_replace('/```(\w+)?\n(.*?)```/s', '<pre><code class="language-$1">$2</code></pre>', $content);
        
        // Convert inline code
        $content = preg_replace('/`([^`]+)`/', '<code>$1</code>', $content);
        
        // Convert line breaks
        $content = nl2br($content);
        
        return $content;
    }

    /**
     * Get message summary for display
     */
    public function getSummary(int $length = 100): string
    {
        return \Str::limit(strip_tags($this->content), $length);
    }

    /**
     * Check if message contains code
     */
    public function containsCode(): bool
    {
        return str_contains($this->content, '```') || str_contains($this->content, '`');
    }

    /**
     * Extract code blocks from message
     */
    public function extractCodeBlocks(): array
    {
        preg_match_all('/```(\w+)?\n(.*?)```/s', $this->content, $matches, PREG_SET_ORDER);
        
        $codeBlocks = [];
        foreach ($matches as $match) {
            $codeBlocks[] = [
                'language' => $match[1] ?? 'text',
                'code' => trim($match[2]),
            ];
        }
        
        return $codeBlocks;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'tokens_used' => $this->tokens_used,
            'processing_time_ms' => $this->processing_time_ms,
            'processing_time_seconds' => $this->processing_time_ms ? round($this->processing_time_ms / 1000, 2) : null,
            'tokens_per_second' => ($this->tokens_used && $this->processing_time_ms) 
                ? round(($this->tokens_used / $this->processing_time_ms) * 1000, 2) 
                : null,
        ];
    }
}