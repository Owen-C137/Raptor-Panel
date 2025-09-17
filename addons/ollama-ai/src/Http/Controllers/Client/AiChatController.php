<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\OllamaAi\Models\AiConversation;
use PterodactylAddons\OllamaAi\Services\AiAssistantService;
use Illuminate\Support\Facades\Auth;

class AiChatController extends Controller
{
    protected $aiAssistant;

    public function __construct(AiAssistantService $aiAssistant)
    {
        $this->aiAssistant = $aiAssistant;
    }

    /**
     * Get the client AI chat interface
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get user's recent conversations
        $conversations = AiConversation::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'context_type' => $conversation->context_type,
                    'last_message' => $conversation->messages()->latest()->first()?->content_preview ?? null,
                    'updated_at' => $conversation->updated_at->diffForHumans(),
                ];
            });

        return view('ollama-ai::client.chat.index', [
            'conversations' => $conversations,
            'ai_enabled' => config('ai.enabled', false),
        ]);
    }

    /**
     * Send a message to the AI assistant
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'conversation_id' => 'nullable|integer|exists:ai_conversations,id',
            'context_type' => 'nullable|string|in:general,server,support,development',
            'context_id' => 'nullable|integer',
        ]);

        try {
            $user = Auth::user();
            
            // Get or create conversation
            $conversation = null;
            if ($request->conversation_id) {
                $conversation = AiConversation::where('id', $request->conversation_id)
                    ->where('user_id', $user->id)
                    ->first();
            }

            if (!$conversation) {
                $conversation = AiConversation::create([
                    'user_id' => $user->id,
                    'title' => $this->generateConversationTitle($request->message),
                    'context_type' => $request->context_type ?? 'general',
                    'context_id' => $request->context_id,
                ]);
            }

            // Send message to AI
            $response = $this->aiAssistant->sendMessage(
                $request->message,
                $conversation,
                $this->buildContext($request)
            );

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'message' => $response['user_message'],
                'ai_response' => $response['ai_response'],
                'processing_time' => $response['processing_time'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process your message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversation history
     */
    public function getConversation(Request $request, int $conversationId): JsonResponse
    {
        $user = Auth::user();
        
        $conversation = AiConversation::with(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])
        ->where('id', $conversationId)
        ->where('user_id', $user->id)
        ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found'
            ], 404);
        }

        $messages = $conversation->messages->map(function ($message) {
            return [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'processing_time' => $message->processing_time,
                'tokens_used' => $message->tokens_used,
            ];
        });

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'context_type' => $conversation->context_type,
                'created_at' => $conversation->created_at->format('Y-m-d H:i:s'),
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Create a new conversation
     */
    public function newConversation(Request $request): JsonResponse
    {
        $request->validate([
            'context_type' => 'nullable|string|in:general,server,support,development',
            'context_id' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        $conversation = AiConversation::create([
            'user_id' => $user->id,
            'title' => $request->title ?? 'New Conversation',
            'context_type' => $request->context_type ?? 'general',
            'context_id' => $request->context_id,
        ]);

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'context_type' => $conversation->context_type,
                'created_at' => $conversation->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Delete a conversation
     */
    public function deleteConversation(Request $request, int $conversationId): JsonResponse
    {
        $user = Auth::user();
        
        $conversation = AiConversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found'
            ], 404);
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully'
        ]);
    }

    /**
     * Get quick suggestions based on context
     */
    public function getQuickSuggestions(Request $request): JsonResponse
    {
        $context = $request->get('context', 'general');
        $contextId = $request->get('context_id');
        
        $suggestions = $this->generateQuickSuggestions($context, $contextId);
        
        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Get AI status for client
     */
    public function getStatus(): JsonResponse
    {
        try {
            $status = [
                'enabled' => config('ai.enabled', false),
                'available' => $this->aiAssistant->isAvailable(),
                'default_model' => config('ai.ollama.default_model'),
                'features' => [
                    'chat' => config('ai.features.chat', true),
                    'analysis' => config('ai.features.analysis', true),
                    'insights' => config('ai.features.insights', true),
                ],
            ];

            return response()->json([
                'success' => true,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get AI status',
            ], 500);
        }
    }

    /**
     * Generate a conversation title from the first message
     */
    protected function generateConversationTitle(string $message): string
    {
        // Keep it simple - use first 50 chars or generate based on content
        $title = trim(substr($message, 0, 50));
        
        if (strlen($message) > 50) {
            $title .= '...';
        }
        
        // If the message looks like a question, keep it as is
        if (str_contains($message, '?')) {
            return $title;
        }
        
        // Otherwise, make it more title-like
        return ucfirst($title);
    }

    /**
     * Build context for AI based on request
     */
    protected function buildContext(Request $request): array
    {
        $context = [
            'user_id' => Auth::id(),
            'user_role' => Auth::user()->root_admin ? 'admin' : 'user',
            'context_type' => $request->context_type ?? 'general',
        ];

        // Add server context if available
        if ($request->context_type === 'server' && $request->context_id) {
            $server = Auth::user()->accessibleServers()
                ->where('id', $request->context_id)
                ->first();
            
            if ($server) {
                $context['server'] = [
                    'id' => $server->id,
                    'name' => $server->name,
                    'description' => $server->description,
                    'status' => $server->status,
                    'egg_name' => $server->egg->name ?? 'unknown',
                ];
            }
        }

        return $context;
    }

    /**
     * Generate quick suggestions based on context
     */
    protected function generateQuickSuggestions(string $context, ?int $contextId = null): array
    {
        $suggestions = [];

        switch ($context) {
            case 'general':
                $suggestions = [
                    'How can I improve my server performance?',
                    'What are the best practices for server management?',
                    'Help me troubleshoot connection issues',
                    'Explain different server configurations',
                ];
                break;
            
            case 'server':
                $suggestions = [
                    'Help me optimize this server',
                    'What could be causing high CPU usage?',
                    'How do I configure the server settings?',
                    'Troubleshoot startup issues',
                ];
                break;
            
            case 'support':
                $suggestions = [
                    'I need help with server configuration',
                    'My server won\'t start, what should I check?',
                    'How do I backup my server data?',
                    'Explain the error logs',
                ];
                break;
            
            case 'development':
                $suggestions = [
                    'Help me set up a development environment',
                    'What are the recommended plugins?',
                    'How do I configure version control?',
                    'Best practices for testing',
                ];
                break;
        }

        return $suggestions;
    }
}