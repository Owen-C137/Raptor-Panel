<?php

namespace PterodactylAddons\OllamaAi\Services;

use PterodactylAddons\OllamaAi\Models\AiConversation;
use PterodactylAddons\OllamaAi\Models\AiMessage;
use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * AI Assistant Service
 * 
 * Handles AI chat functionality, conversation management,
 * and context-aware responses for the Pterodactyl panel.
 */
class AiAssistantService
{
    protected OllamaService $ollamaService;

    public function __construct(OllamaService $ollamaService)
    {
        $this->ollamaService = $ollamaService;
    }

    /**
     * Start a new conversation or get existing one
     */
    public function getOrCreateConversation(
        User $user, 
        string $contextType = 'general', 
        int $contextId = null
    ): AiConversation {
        // Try to find existing active conversation
        $conversation = AiConversation::where('user_id', $user->id)
            ->byContext($contextType, $contextId)
            ->active()
            ->first();

        if (!$conversation) {
            $conversation = AiConversation::create([
                'user_id' => $user->id,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'status' => 'active',
                'started_at' => now(),
                'last_message_at' => now(),
            ]);
        }

        return $conversation;
    }

    /**
     * Send a message and get AI response
     */
    public function sendMessage(
        AiConversation $conversation, 
        string $message, 
        string $modelType = 'chat'
    ): array {
        $startTime = microtime(true);

        try {
            // Create user message
            $userMessage = AiMessage::create([
                'conversation_id' => $conversation->id,
                'role' => AiMessage::ROLE_USER,
                'content' => $message,
                'status' => AiMessage::STATUS_COMPLETED,
            ]);

            // Create pending AI message
            $aiMessage = AiMessage::create([
                'conversation_id' => $conversation->id,
                'role' => AiMessage::ROLE_ASSISTANT,
                'content' => '',
                'status' => AiMessage::STATUS_PROCESSING,
            ]);

            // Get conversation context
            $context = $this->buildConversationContext($conversation);

            // Add system context based on conversation type
            $systemContext = $this->getSystemContext($conversation);
            if ($systemContext) {
                array_unshift($context, [
                    'role' => 'system',
                    'content' => $systemContext
                ]);
            }

            // Get AI response
            $response = $this->ollamaService->chat($message, null, $context);

            if ($response) {
                $processingTime = (microtime(true) - $startTime) * 1000;

                // Update AI message with response
                $aiMessage->update([
                    'content' => $response['response'],
                    'model_used' => $response['model'],
                    'tokens_used' => $response['tokens_used'],
                    'processing_time_ms' => (int) $processingTime,
                    'status' => AiMessage::STATUS_COMPLETED,
                    'metadata' => [
                        'eval_duration' => $response['eval_duration'] ?? null,
                        'created_at' => $response['created_at'] ?? null,
                    ],
                ]);

                // Update conversation
                $conversation->touchLastMessage();
                $conversation->generateTitle();

                return [
                    'success' => true,
                    'user_message' => $userMessage,
                    'ai_message' => $aiMessage,
                    'conversation' => $conversation,
                ];
            } else {
                $aiMessage->markFailed('Failed to get response from AI model');

                return [
                    'success' => false,
                    'error' => 'Failed to get AI response',
                    'user_message' => $userMessage,
                    'ai_message' => $aiMessage,
                ];
            }
        } catch (\Exception $e) {
            Log::error('AI chat failed', [
                'conversation_id' => $conversation->id,
                'user_id' => $conversation->user_id,
                'error' => $e->getMessage(),
            ]);

            if (isset($aiMessage)) {
                $aiMessage->markFailed($e->getMessage());
            }

            return [
                'success' => false,
                'error' => 'An error occurred while processing your message',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build conversation context for AI
     */
    protected function buildConversationContext(AiConversation $conversation): array
    {
        $maxMessages = config('ai.limits.max_chat_history', 10);
        return $conversation->getContextForAi($maxMessages);
    }

    /**
     * Get system context based on conversation type
     */
    protected function getSystemContext(AiConversation $conversation): ?string
    {
        switch ($conversation->context_type) {
            case 'server':
                return "You are an AI assistant helping with Pterodactyl game server management. " .
                       "Provide helpful advice about server configuration, troubleshooting, and optimization. " .
                       "Focus on practical solutions and best practices for game server hosting.";

            case 'admin':
                return "You are an AI assistant for Pterodactyl panel administrators. " .
                       "Help with panel management, user administration, node configuration, and system maintenance. " .
                       "Provide secure and efficient solutions for hosting platform management.";

            case 'support':
                return "You are a helpful support assistant for Pterodactyl panel users. " .
                       "Provide clear, step-by-step guidance for common issues and questions. " .
                       "Be patient and thorough in your explanations.";

            case 'code':
                return "You are a programming assistant specializing in server configurations and scripts. " .
                       "Help with configuration files, automation scripts, and troubleshooting code issues. " .
                       "Provide secure, well-documented code examples.";

            case 'general':
            default:
                return "You are a helpful AI assistant for Pterodactyl panel users. " .
                       "Provide accurate information about game server hosting, panel features, and general support. " .
                       "Be concise but thorough in your responses.";
        }
    }

    /**
     * Get conversation history for user
     */
    public function getUserConversations(User $user, int $limit = 20): array
    {
        return AiConversation::where('user_id', $user->id)
            ->with(['latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'title' => $conversation->title ?: 'New Conversation',
                    'context_type' => $conversation->context_type,
                    'last_message_at' => $conversation->last_message_at,
                    'message_count' => $conversation->messages()->count(),
                    'latest_message' => $conversation->latestMessage?->getSummary(100),
                ];
            })
            ->toArray();
    }

    /**
     * Archive old conversations
     */
    public function archiveOldConversations(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return AiConversation::where('last_message_at', '<', $cutoffDate)
            ->where('status', 'active')
            ->update(['status' => 'archived']);
    }

    /**
     * Delete old conversations and messages
     */
    public function cleanupOldData(): array
    {
        $retentionDays = config('ai.retention.conversations', 30);
        
        if (!$retentionDays) {
            return ['conversations_deleted' => 0, 'messages_deleted' => 0];
        }

        $cutoffDate = now()->subDays($retentionDays);
        
        // Get conversation IDs to delete
        $conversationIds = AiConversation::where('last_message_at', '<', $cutoffDate)->pluck('id');
        
        // Delete messages first (foreign key constraint)
        $messagesDeleted = AiMessage::whereIn('conversation_id', $conversationIds)->delete();
        
        // Delete conversations
        $conversationsDeleted = AiConversation::where('last_message_at', '<', $cutoffDate)->delete();
        
        Log::info('Cleaned up old AI data', [
            'conversations_deleted' => $conversationsDeleted,
            'messages_deleted' => $messagesDeleted,
            'retention_days' => $retentionDays,
        ]);

        return [
            'conversations_deleted' => $conversationsDeleted,
            'messages_deleted' => $messagesDeleted,
        ];
    }

    /**
     * Get AI usage statistics
     */
    public function getUsageStatistics(User $user = null): array
    {
        $query = AiConversation::query();
        
        if ($user) {
            $query->where('user_id', $user->id);
        }

        $conversationsCount = $query->count();
        $messagesQuery = AiMessage::query();
        
        if ($user) {
            $messagesQuery->whereHas('conversation', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $totalMessages = $messagesQuery->count();
        $totalTokens = $messagesQuery->sum('tokens_used');
        $avgProcessingTime = $messagesQuery->avg('processing_time_ms');

        return [
            'total_conversations' => $conversationsCount,
            'total_messages' => $totalMessages,
            'total_tokens' => $totalTokens,
            'avg_processing_time_ms' => $avgProcessingTime ? round($avgProcessingTime, 2) : 0,
            'user_specific' => $user !== null,
        ];
    }

    /**
     * Export conversation for user
     */
    public function exportConversation(AiConversation $conversation): array
    {
        return [
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'context_type' => $conversation->context_type,
                'started_at' => $conversation->started_at,
                'last_message_at' => $conversation->last_message_at,
                'stats' => $conversation->getStats(),
            ],
            'messages' => $conversation->messages->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                    'created_at' => $message->created_at,
                    'model_used' => $message->model_used,
                    'performance' => $message->getPerformanceMetrics(),
                ];
            })->toArray(),
        ];
    }
}