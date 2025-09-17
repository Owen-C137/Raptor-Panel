<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use PterodactylAddons\OllamaAi\Models\AiCodeTemplate;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Admin controller for managing AI code templates.
 */
class AiTemplateController extends Controller
{
    /**
     * Display the templates management page.
     */
    public function index(Request $request): View
    {
        return view('ollama-ai::admin.templates.index');
    }

    /**
     * Get templates data for DataTables.
     */
    public function getData(Request $request): JsonResponse
    {
        $query = AiCodeTemplate::with(['creator:id,email,username'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('search') && $request->search['value']) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->has('category') && $request->category !== '') {
            $query->where('category', $request->category);
        }

        if ($request->has('language') && $request->language !== '') {
            $query->where('language', $request->language);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active === '1');
        }

        $templates = $query->paginate(50);

        return response()->json([
            'data' => $templates->items(),
            'recordsTotal' => AiCodeTemplate::count(),
            'recordsFiltered' => $templates->total(),
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): View
    {
        return view('ollama-ai::admin.templates.create');
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:191|unique:ai_code_templates',
            'category' => 'required|string|max:100',
            'description' => 'required|string',
            'template_code' => 'required|string',
            'language' => 'required|string|max:50',
            'parameters' => 'nullable|json',
            'is_active' => 'boolean'
        ]);

        try {
            $template = AiCodeTemplate::create([
                'name' => $request->name,
                'category' => $request->category,
                'description' => $request->description,
                'template_code' => $request->template_code,
                'language' => $request->language,
                'parameters' => $request->parameters ? json_decode($request->parameters, true) : null,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => Auth::id(),
                'usage_count' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template created successfully.',
                'template' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific template.
     */
    public function show(AiCodeTemplate $template): View
    {
        $template->load('creator');

        return view('ollama-ai::admin.templates.show', compact('template'));
    }

    /**
     * Show the form for editing a template.
     */
    public function edit(AiCodeTemplate $template): View
    {
        return view('ollama-ai::admin.templates.edit', compact('template'));
    }

    /**
     * Update a template.
     */
    public function update(Request $request, AiCodeTemplate $template): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:191|unique:ai_code_templates,name,' . $template->id,
            'category' => 'required|string|max:100',
            'description' => 'required|string',
            'template_code' => 'required|string',
            'language' => 'required|string|max:50',
            'parameters' => 'nullable|json',
            'is_active' => 'boolean'
        ]);

        try {
            $template->update([
                'name' => $request->name,
                'category' => $request->category,
                'description' => $request->description,
                'template_code' => $request->template_code,
                'language' => $request->language,
                'parameters' => $request->parameters ? json_decode($request->parameters, true) : null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template updated successfully.',
                'template' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a template.
     */
    public function destroy(AiCodeTemplate $template): JsonResponse
    {
        try {
            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle template active status.
     */
    public function toggleActive(AiCodeTemplate $template): JsonResponse
    {
        try {
            $template->update(['is_active' => !$template->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Template status updated successfully.',
                'is_active' => $template->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update template status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get template categories.
     */
    public function getCategories(): JsonResponse
    {
        $categories = AiCodeTemplate::distinct()->pluck('category')->filter()->values();

        return response()->json($categories);
    }

    /**
     * Get template statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_templates' => AiCodeTemplate::count(),
            'active_templates' => AiCodeTemplate::where('is_active', true)->count(),
            'inactive_templates' => AiCodeTemplate::where('is_active', false)->count(),
            'total_usage' => AiCodeTemplate::sum('usage_count'),
            'most_used_template' => AiCodeTemplate::orderBy('usage_count', 'desc')->first(),
            'categories_count' => AiCodeTemplate::distinct()->count('category'),
            'languages_breakdown' => AiCodeTemplate::selectRaw('language, COUNT(*) as count')
                ->groupBy('language')
                ->pluck('count', 'language')
                ->toArray(),
            'categories_breakdown' => AiCodeTemplate::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
        ];

        return response()->json($stats);
    }
}