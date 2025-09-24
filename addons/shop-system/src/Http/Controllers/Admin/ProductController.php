<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopProduct;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Egg;
use Pterodactyl\Http\Requests\Admin\Shop\ProductStoreRequest;
use Pterodactyl\Http\Requests\Admin\Shop\ProductUpdateRequest;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $status = $request->get('status');
        
        $products = ShopProduct::query()
            ->with(['category'])
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($category, function ($query, $category) {
                return $query->where('category_id', $category);
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->withCount('orders')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25);
        
        $categories = ShopCategory::orderBy('name')->get();
        
        return view('shop::admin.products.index', compact('products', 'categories', 'search', 'category', 'status'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $categories = ShopCategory::orderBy('name')->get();
        $nodes = Node::orderBy('name')->get();
        $locations = Location::orderBy('long')->get();
        $eggs = Egg::with('nest')->orderBy('name')->get();
        
        return view('shop::admin.products.create', compact('categories', 'nodes', 'locations', 'eggs'));
    }

    /**
     * Store a newly created product
     */
    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        
        // Handle sort order
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = ShopProduct::max('sort_order') + 1;
        }
        
        // Handle JSON fields
        if (isset($data['allowed_nodes'])) {
            $data['allowed_nodes'] = is_array($data['allowed_nodes']) ? $data['allowed_nodes'] : [];
        }
        
        if (isset($data['allowed_locations'])) {
            $data['allowed_locations'] = is_array($data['allowed_locations']) ? $data['allowed_locations'] : [];
        }
        
        ShopProduct::create($data);
        
        return redirect()->route('admin.shop.products')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified product
     */
    public function show(ShopProduct $product)
    {
        $product->load(['category', 'plans', 'orders.user']);
        
        return view('shop::admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing a product
     */
    public function edit(ShopProduct $product)
    {
        $categories = ShopCategory::orderBy('name')->get();
        $nodes = Node::orderBy('name')->get();
        $locations = Location::orderBy('long')->get();
        $eggs = Egg::with('nest')->orderBy('name')->get();
        
        return view('shop::admin.products.edit', compact('product', 'categories', 'nodes', 'locations', 'eggs'));
    }

    /**
     * Update the specified product
     */
    public function update(ProductUpdateRequest $request, ShopProduct $product): RedirectResponse
    {
        $data = $request->validated();
        
        // Handle JSON fields
        if (isset($data['allowed_nodes'])) {
            $data['allowed_nodes'] = is_array($data['allowed_nodes']) ? $data['allowed_nodes'] : [];
        }
        
        if (isset($data['allowed_locations'])) {
            $data['allowed_locations'] = is_array($data['allowed_locations']) ? $data['allowed_locations'] : [];
        }
        
        $product->update($data);
        
        return redirect()->route('admin.shop.products')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product
     */
    public function destroy(ShopProduct $product): RedirectResponse
    {
        if ($product->orders()->count() > 0) {
            return redirect()->route('admin.shop.products')
                ->with('error', 'Cannot delete product with existing orders.');
        }
        
        $product->delete();
        
        return redirect()->route('admin.shop.products')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Toggle product status
     */
    public function toggleStatus(ShopProduct $product): RedirectResponse
    {
        $product->update([
            'status' => $product->status === 'active' ? 'inactive' : 'active'
        ]);
        
        return redirect()->route('admin.shop.products')
            ->with('success', 'Product status updated successfully.');
    }

    /**
     * Duplicate a product
     */
    public function duplicate(ShopProduct $product): RedirectResponse
    {
        $duplicated = $product->replicate();
        $duplicated->name = $product->name . ' (Copy)';
        $duplicated->slug = $product->slug . '-copy-' . time();
        $duplicated->status = 'inactive';
        $duplicated->save();

        return redirect()->route('admin.shop.products.edit', $duplicated)
            ->with('success', 'Product duplicated successfully.');
    }
}
