<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use PterodactylAddons\OllamaAi\Models\AiConversation;
use PterodactylAddons\OllamaAi\Models\AiMessage;
use Pterodactyl\Http\Controllers\Controller;

/**
 * Admin controller for managing AI conversations.
 */
class AiConversationController extends Controller
{
    /**
     * Display the conversations management page.
     */
    public function index(Request $request): View
    {
        return view('ollama-ai::admin.conversations.index');
    }

    /**
     * Get conversations data for DataTables.
     */
    public function getData(Request $request): JsonResponse
    {
        $query = AiConversation::with(['user:id,email,username'])
            ->withCount('messages')
            ->orderBy('updated_at', 'desc');

        // Apply filters
        if ($request->has('search') && $request->search['value']) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('email', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $conversations = $query->paginate(50);

        return response()->json([
            'data' => $conversations->items(),
            'recordsTotal' => AiConversation::count(),
            'recordsFiltered' => $conversations->total(),
        ]);
    }

    /**
     * Show a specific conversation.
     */
    public function show(AiConversation $conversation): View
    {
        $conversation->load(['user', 'messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        return view('ollama-ai::admin.conversations.show', compact('conversation'));
    }

    /**
     * Delete a conversation.
     */
    public function destroy(AiConversation $conversation): JsonResponse
    {
        try {
            $conversation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete conversations.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:ai_conversations,id'
        ]);

        try {
            AiConversation::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' conversations deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_conversations' => AiConversation::count(),
            'active_conversations' => AiConversation::where('status', 'active')->count(),
            'total_messages' => AiMessage::count(),
            'average_messages_per_conversation' => AiConversation::withCount('messages')->get()->avg('messages_count'),
            'conversations_today' => AiConversation::whereDate('created_at', today())->count(),
            'conversations_this_week' => AiConversation::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'conversations_this_month' => AiConversation::whereMonth('created_at', now()->month)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Export conversations data.
     */
    public function export(Request $request): JsonResponse
    {
        // Implementation would export conversations to CSV/Excel
        // For now, return a placeholder response
        return response()->json([
            'success' => true,
            'message' => 'Export feature will be implemented in a future update.'
        ]);
    }
}