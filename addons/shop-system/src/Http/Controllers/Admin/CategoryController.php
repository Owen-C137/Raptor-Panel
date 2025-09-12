<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use Pterodactyl\Http\Requests\Admin\Shop\CategoryStoreRequest;
use Pterodactyl\Http\Requests\Admin\Shop\CategoryUpdateRequest;

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
        
        return redirect()->route('admin.shop.categories')
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
        
        return redirect()->route('admin.shop.categories')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category
     */
    public function destroy(ShopCategory $category): RedirectResponse
    {
        if ($category->plans()->count() > 0) {
            return redirect()->route('admin.shop.categories')
                ->with('error', 'Cannot delete category with existing plans.');
        }
        
        $category->delete();
        
        return redirect()->route('admin.shop.categories')
            ->with('success', 'Category deleted successfully.');
    }
}
