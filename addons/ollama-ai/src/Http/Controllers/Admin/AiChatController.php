<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use PterodactylAddons\OllamaAi\Models\AiConversation;
use PterodactylAddons\OllamaAi\Models\AiMessage;
use PterodactylAddons\OllamaAi\Services\OllamaService;
use Pterodactyl\Http\Controllers\Controller;

/**
 * Admin chat controller for direct AI interaction by administrators.
 */
class AiChatController extends Controller
{
    protected $ollamaService;

    public function __construct(OllamaService $ollamaService)
    {
        $this->ollamaService = $ollamaService;
    }

    /**
     * Display the admin chat interface.
     */
    public function index(Request $request): View
    {
        $availableModels = $this->getAvailableModels();
        $recentConversations = $this->getRecentAdminConversations();
        
        return view('ollama-ai::admin.chat.index', compact('availableModels', 'recentConversations'));
    }

    /**
     * Get available AI models.
     */
    public function models(Request $request): JsonResponse
    {
        try {
            $models = $this->ollamaService->getModels();
            
            if (!$models['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $models['error'] ?? 'Failed to fetch models'
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'models' => $models['models'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch models: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get connection status.
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $isConnected = $this->ollamaService->testConnection();
            
            return response()->json([
                'success' => true,
                'status' => [
                    'connected' => $isConnected
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a message to AI and get response.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'model' => 'required|string|max:100',
            'conversation_id' => 'nullable|integer|exists:ai_conversations,id',
            'system_prompt' => 'nullable|string|max:1000'
        ]);

        try {
            $user = Auth::user();
            $conversationId = $request->conversation_id;

            // Create or get conversation
            if (!$conversationId) {
                $conversation = AiConversation::create([
                    'user_id' => $user->id,
                    'title' => $this->generateConversationTitle($request->message),
                    'context_type' => 'admin',
                    'status' => 'active',
                    'model_used' => $request->model,
                    'started_at' => now(),
                    'last_message_at' => now(),
                    'metadata' => json_encode([
                        'admin_session' => true,
                        'system_prompt' => $request->system_prompt ?? 'You are an AI assistant helping a Pterodactyl Panel administrator. Provide helpful, technical responses focused on server management, panel administration, and troubleshooting.'
                    ])
                ]);
                $conversationId = $conversation->id;
            } else {
                $conversation = AiConversation::findOrFail($conversationId);
                $conversation->update([
                    'last_message_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Save user message
            $userMessage = AiMessage::create([
                'conversation_id' => $conversationId,
                'role' => 'user',
                'content' => $request->message,
                'metadata' => json_encode([
                    'model' => $request->model,
                    'timestamp' => now()->toISOString()
                ])
            ]);

            // Get conversation history for context
            $messages = $this->getConversationHistory($conversationId);
            
            // Add system prompt if specified
            $systemPrompt = $request->system_prompt ?? 'You are an AI assistant helping a Pterodactyl Panel administrator. Provide helpful, technical responses focused on server management, panel administration, and troubleshooting.';
            
            // Send to AI
            $aiResponse = $this->ollamaService->sendMessage(
                $request->message, 
                $request->model,
                $messages,
                $systemPrompt
            );

            if (!$aiResponse['success']) {
                throw new \Exception($aiResponse['error'] ?? 'AI service unavailable');
            }

            // Save AI response
            $aiMessage = AiMessage::create([
                'conversation_id' => $conversationId,
                'role' => 'assistant',
                'content' => $aiResponse['content'],
                'metadata' => json_encode([
                    'model' => $request->model,
                    'timestamp' => now()->toISOString(),
                    'response_time' => $aiResponse['response_time'] ?? null,
                    'tokens_used' => $aiResponse['tokens'] ?? null
                ])
            ]);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversationId,
                'message' => [
                    'id' => $aiMessage->id,
                    'role' => 'assistant',
                    'content' => $aiResponse['content'],
                    'timestamp' => $aiMessage->created_at->toISOString()
                ],
                'user_message' => [
                    'id' => $userMessage->id,
                    'role' => 'user',
                    'content' => $request->message,
                    'timestamp' => $userMessage->created_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a new conversation.
     */
    public function newConversation(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $conversation = AiConversation::create([
                'user_id' => $user->id,
                'title' => 'New Admin Chat',
                'context_type' => 'admin',
                'status' => 'active',
                'model_used' => $request->input('model', env('AI_DEFAULT_MODEL', 'llama3.2')),
                'started_at' => now(),
                'last_message_at' => now(),
                'metadata' => json_encode([
                    'admin_session' => true,
                    'created_by_admin' => true
                ])
            ]);

            return response()->json([
                'success' => true,
                'conversation' => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'created_at' => $conversation->created_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation with messages.
     */
    public function getConversation(AiConversation $conversation): JsonResponse
    {
        try {
            $conversation->load(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }]);

            return response()->json([
                'success' => true,
                'conversation' => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'model_used' => $conversation->model_used,
                    'created_at' => $conversation->created_at->toISOString(),
                    'messages' => $conversation->messages->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'role' => $message->role,
                            'content' => $message->content,
                            'timestamp' => $message->created_at->toISOString()
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a conversation.
     */
    public function deleteConversation(AiConversation $conversation): JsonResponse
    {
        try {
            $conversation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available AI models API endpoint.
     */
    public function getModelsApi(): JsonResponse
    {
        try {
            $models = $this->ollamaService->getModels();
            
            return response()->json([
                'success' => true,
                'models' => $models
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'models' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get AI system status for admin chat.
     */
    public function getStatus(): JsonResponse
    {
        try {
            $status = $this->ollamaService->getSystemStatus();
            
            return response()->json([
                'success' => true,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => ['connected' => false, 'error' => $e->getMessage()]
            ]);
        }
    }

    /**
     * Get conversation history for context.
     */
    private function getConversationHistory(int $conversationId): array
    {
        $messages = AiMessage::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->take(10) // Last 10 messages for context
            ->get()
            ->reverse()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content
                ];
            })
            ->toArray();

        return $messages;
    }

    /**
     * Get recent admin conversations.
     */
    private function getRecentAdminConversations()
    {
        return AiConversation::where('user_id', Auth::id())
            ->where('context_type', 'admin')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get available models from service.
     */
    private function getAvailableModels()
    {
        try {
            $result = $this->ollamaService->getModels();
            return $result['models'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate a conversation title from the first message.
     */
    private function generateConversationTitle(string $message): string
    {
        $title = substr($message, 0, 50);
        if (strlen($message) > 50) {
            $title .= '...';
        }
        
        return 'Admin: ' . $title;
    }
}