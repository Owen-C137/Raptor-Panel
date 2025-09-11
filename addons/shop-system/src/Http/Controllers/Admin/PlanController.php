<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopPlan;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Http\Requests\PlanStoreRequest;
use PterodactylAddons\ShopSystem\Http\Requests\PlanUpdateRequest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Egg;

class PlanController extends Controller
{
    /**
     * Display a listing of plans
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $status = $request->get('status');
        
        $plans = ShopPlan::query()
            ->with(['category'])
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($category, function ($query, $category) {
                return $query->where('category_id', $category);
            })
            ->when($status !== null, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);
        
        $categories = ShopCategory::where('active', true)->orderBy('name')->get();
        
        return view('shop::admin.plans.index', compact('plans', 'categories'));
    }

    /**
     * Show the form for creating a new plan
     */
    public function create()
    {
        $categories = ShopCategory::where('active', true)->orderBy('name')->get();
        $nodes = Node::orderBy('name')->get();
        $locations = Location::orderBy('long')->get();
        $eggs = Egg::with('nest')->orderBy('name')->get();
        
        return view('shop::admin.plans.create', compact('categories', 'nodes', 'locations', 'eggs'));
    }

    /**
     * Store a newly created plan
     */
    public function store(PlanStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        
        // Handle sort order
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = ShopPlan::where('category_id', $data['category_id'])->max('sort_order') + 1;
        }
        
        // Handle JSON fields
        if (isset($data['billing_cycles'])) {
            $data['billing_cycles'] = $this->processBillingCycles($data['billing_cycles']);
        }
        
        if (isset($data['server_limits'])) {
            $data['server_limits'] = $this->processServerLimits($data['server_limits']);
        }
        
        if (isset($data['server_feature_limits'])) {
            $data['server_feature_limits'] = $this->processServerFeatureLimits($data['server_feature_limits']);
        }
        
        $plan = ShopPlan::create($data);
        
        return redirect()->route('admin.shop.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    /**
     * Display the specified plan
     */
    public function show(ShopPlan $plan)
    {
        $plan->load(['category', 'orders.user']);
        
        return view('shop::admin.plans.show', compact('plan'));
    }

    /**
     * Show the form for editing a plan
     */
    public function edit(ShopPlan $plan)
    {
        $categories = ShopCategory::where('active', true)->orderBy('name')->get();
        $nodes = Node::orderBy('name')->get();
        $locations = Location::orderBy('long')->get();
        $eggs = Egg::with('nest')->orderBy('name')->get();
        
        // Extract limits arrays for the form
        $limits = $plan->server_limits ?? [];
        $featureLimits = $plan->server_feature_limits ?? [];
        
        return view('shop::admin.plans.edit', compact('plan', 'categories', 'nodes', 'locations', 'eggs', 'limits', 'featureLimits'));
    }

    /**
     * Update the specified plan
     */
    public function update(PlanUpdateRequest $request, ShopPlan $plan): RedirectResponse
    {
        $data = $request->validated();
        
        // Handle JSON fields
        if (isset($data['billing_cycles'])) {
            $data['billing_cycles'] = $this->processBillingCycles($data['billing_cycles']);
        }
        
        if (isset($data['server_limits'])) {
            $data['server_limits'] = $this->processServerLimits($data['server_limits']);
        }
        
        if (isset($data['server_feature_limits'])) {
            $data['server_feature_limits'] = $this->processServerFeatureLimits($data['server_feature_limits']);
        }
        
        $plan->update($data);
        
        return redirect()->route('admin.shop.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    /**
     * Remove the specified plan
     */
    public function destroy(Request $request, ShopPlan $plan)
    {
        try {
            // Check if plan has any orders
            if ($plan->orders()->exists()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete plan that has existing orders.'
                    ], 400);
                }
                
                return redirect()->back()
                    ->with('error', 'Cannot delete plan that has existing orders.');
            }
            
            $plan->delete();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Plan deleted successfully.'
                ]);
            }
            
            return redirect()->route('admin.shop.plans.index')
                ->with('success', 'Plan deleted successfully.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete plan: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete plan: ' . $e->getMessage());
        }
    }

    /**
     * Toggle plan status
     */
    public function toggleStatus(Request $request, ShopPlan $plan)
    {
        $newStatus = $plan->status === 'active' ? 'inactive' : 'active';
        $plan->update(['status' => $newStatus]);
        
        $status = $newStatus === 'active' ? 'activated' : 'deactivated';
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Plan has been {$status} successfully.",
                'status' => $plan->status
            ]);
        }
        
        return redirect()->back()
            ->with('success', "Plan has been {$status} successfully.");
    }

    /**
     * Duplicate a plan
     */
    public function duplicate(Request $request, ShopPlan $plan)
    {
        try {
            $newPlan = $plan->replicate();
            $newPlan->name = $plan->name . ' (Copy)';
            $newPlan->status = 'inactive';
            $newPlan->save();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Plan duplicated successfully. Please review and update as needed.',
                    'redirect' => route('admin.shop.plans.edit', $newPlan)
                ]);
            }
            
            return redirect()->route('admin.shop.plans.edit', $newPlan)
                ->with('success', 'Plan duplicated successfully. Please review and update as needed.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to duplicate plan: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to duplicate plan: ' . $e->getMessage());
        }
    }

    /**
     * Process billing cycles data
     */
    private function processBillingCycles($cycles)
    {
        if (is_string($cycles)) {
            return json_decode($cycles, true) ?? [];
        }
        
        return $cycles ?? [];
    }

    /**
     * Process server limits data
     */
    private function processServerLimits($limits)
    {
        if (is_string($limits)) {
            return json_decode($limits, true) ?? [];
        }
        
        return $limits ?? [];
    }

    /**
     * Process server feature limits data
     */
    private function processServerFeatureLimits($limits)
    {
        if (is_string($limits)) {
            return json_decode($limits, true) ?? [];
        }
        
        return $limits ?? [];
    }
}
