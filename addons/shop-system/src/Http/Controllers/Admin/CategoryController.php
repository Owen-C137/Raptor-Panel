<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Http\Requests\Admin\CategoryStoreRequest;
use PterodactylAddons\ShopSystem\Http\Requests\Admin\CategoryUpdateRequest;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $categories = ShopCategory::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->withCount('plans')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25);
        
        return view('shop::admin.categories.index', compact('categories', 'search'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        // Get all existing categories that could be parents
        $parentCategories = ShopCategory::orderBy('name')->get();
        
        return view('shop::admin.categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created category
     */
    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        
        // Handle sort order
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = ShopCategory::max('sort_order') + 1;
        }
        
        ShopCategory::create($data);
        
        return redirect()->route('admin.shop.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified category
     */
    public function show(ShopCategory $category)
    {
        $category->load(['plans', 'children.plans']);
        
        return view('shop::admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing a category
     */
    public function edit(ShopCategory $category)
    {
        // Load required relationships for the view
        $category->load(['plans', 'children.plans']);
        
        // Get all categories except the current one as potential parents
        $parentCategories = ShopCategory::where('id', '!=', $category->id)->orderBy('name')->get();
        
        return view('shop::admin.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Update the specified category
     */
    public function update(CategoryUpdateRequest $request, ShopCategory $category): RedirectResponse
    {
        $category->update($request->validated());
        
        return redirect()->route('admin.shop.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category
     */
    public function destroy(ShopCategory $category): RedirectResponse
    {
        if ($category->plans()->count() > 0) {
            return redirect()->route('admin.shop.categories.index')
                ->with('error', 'Cannot delete category with existing plans.');
        }
        
        $category->delete();
        
        return redirect()->route('admin.shop.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Toggle the status of a category
     */
    public function toggleStatus(ShopCategory $category): JsonResponse
    {
        $category->active = !$category->active;
        $category->save();
        
        return response()->json([
            'success' => true,
            'active' => $category->active,
            'message' => 'Category status updated successfully.'
        ]);
    }

    /**
     * Handle batch actions on categories
     */
    public function batchAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:delete,toggle_status',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:shop_categories,id'
        ]);

        $categoryIds = $request->input('category_ids');
        $action = $request->input('action');
        $affectedCount = 0;
        $errors = [];

        switch ($action) {
            case 'delete':
                foreach ($categoryIds as $categoryId) {
                    $category = ShopCategory::find($categoryId);
                    if ($category) {
                        if ($category->plans()->count() > 0) {
                            $errors[] = "Cannot delete '{$category->name}' - it contains plans.";
                        } else {
                            $category->delete();
                            $affectedCount++;
                        }
                    }
                }
                $message = $affectedCount > 0 ? "{$affectedCount} categories deleted successfully." : 'No categories were deleted.';
                break;

            case 'toggle_status':
                $categories = ShopCategory::whereIn('id', $categoryIds)->get();
                foreach ($categories as $category) {
                    $category->active = !$category->active;
                    $category->save();
                    $affectedCount++;
                }
                $message = "{$affectedCount} categories status toggled successfully.";
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action.'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'affected_count' => $affectedCount,
            'errors' => $errors
        ]);
    }
}
