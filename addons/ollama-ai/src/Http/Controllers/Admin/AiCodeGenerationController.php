<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use PterodactylAddons\OllamaAi\Models\AiCodeGeneration;
use PterodactylAddons\OllamaAi\Models\AiCodeTemplate;
use Pterodactyl\Http\Controllers\Controller;

/**
 * Admin controller for managing AI code generation and templates.
 */
class AiCodeGenerationController extends Controller
{
    /**
     * Display the code generation management page.
     */
    public function index(Request $request): View
    {
        return view('ollama-ai::admin.code-generation.index');
    }

    /**
     * Get code generations data for DataTables.
     */
    public function getData(Request $request): JsonResponse
    {
        $query = AiCodeGeneration::with(['user:id,email,username'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('search') && $request->search['value']) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                  ->orWhere('generated_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('email', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        if ($request->has('is_successful') && $request->is_successful !== '') {
            $query->where('is_successful', $request->is_successful === '1');
        }

        $generations = $query->paginate(50);

        return response()->json([
            'data' => $generations->items(),
            'recordsTotal' => AiCodeGeneration::count(),
            'recordsFiltered' => $generations->total(),
        ]);
    }

    /**
     * Show a specific code generation.
     */
    public function show(AiCodeGeneration $codeGeneration): View
    {
        $codeGeneration->load('user');

        return view('ollama-ai::admin.code-generation.show', compact('codeGeneration'));
    }

    /**
     * Delete a code generation record.
     */
    public function destroy(AiCodeGeneration $codeGeneration): JsonResponse
    {
        try {
            $codeGeneration->delete();

            return response()->json([
                'success' => true,
                'message' => 'Code generation record deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete code generation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get code generation statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_generations' => AiCodeGeneration::count(),
            'successful_generations' => AiCodeGeneration::where('is_successful', true)->count(),
            'failed_generations' => AiCodeGeneration::where('is_successful', false)->count(),
            'average_confidence' => AiCodeGeneration::avg('ai_confidence'),
            'generations_today' => AiCodeGeneration::whereDate('created_at', today())->count(),
            'generations_this_week' => AiCodeGeneration::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'generations_this_month' => AiCodeGeneration::whereMonth('created_at', now()->month)->count(),
            'types_breakdown' => AiCodeGeneration::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];

        return response()->json($stats);
    }
}