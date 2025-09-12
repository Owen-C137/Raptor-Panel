<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopPlan;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Http\Requests\PlanStoreRequest;
use PterodactylAddons\ShopSystem\Http\Requests\PlanUpdateRequest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\ServerDeletionService;

class PlanController extends Controller
{
    /**
     * Display a listing of plans
     */
    public function index(): View
    {
        $plans = ShopPlan::with(['category', 'egg'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25);

        // Load associated servers for each plan using the alternative method
        foreach ($plans as $plan) {
            $plan->associatedServers = $plan->getAssociatedServers();
        }

        // Get categories for the filter dropdown
        $categories = ShopCategory::orderBy('name')->get();

        return view('shop::admin.plans.index', compact('plans', 'categories'));
    }

    /**
     * Show the form for creating a new plan
     */
    public function create(): View
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
    public function show(ShopPlan $plan): View
    {
        $plan->load(['category', 'orders.user']);
        
        return view('shop::admin.plans.show', compact('plan'));
    }

    /**
     * Show the form for editing a plan
     */
    public function edit(ShopPlan $plan): View
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
            $deletedServers = 0;
            
            // Check if we should delete connected servers
            $deleteServers = $request->boolean('delete_servers', false);
            
            // Get associated servers before deleting the plan
            $associatedServers = collect();
            if ($deleteServers) {
                $associatedServers = $plan->getAssociatedServers();
            }
            
            // Check if plan has any orders (unless we're force deleting)
            if ($plan->orders()->exists() && !$deleteServers) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete plan that has existing orders. Consider deleting connected servers first.'
                    ], 400);
                }
                
                return redirect()->back()
                    ->with('error', 'Cannot delete plan that has existing orders. Consider deleting connected servers first.');
            }
            
            // Delete associated servers if requested
            if ($deleteServers && $associatedServers->isNotEmpty()) {
                $serverDeletionService = app(ServerDeletionService::class);
                
                foreach ($associatedServers as $server) {
                    try {
                        $serverDeletionService->handle($server);
                        $deletedServers++;
                    } catch (\Exception $e) {
                        // Log the error but continue with other servers
                        \Log::warning("Failed to delete server {$server->id}: " . $e->getMessage());
                    }
                }
            }
            
            // Delete the plan
            $plan->delete();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Plan deleted successfully.',
                    'servers_deleted' => $deletedServers
                ]);
            }
            
            $message = 'Plan deleted successfully.';
            if ($deletedServers > 0) {
                $message .= " {$deletedServers} server(s) were also deleted.";
            }
            
            return redirect()->route('admin.shop.plans.index')
                ->with('success', $message);
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
            $cycles = json_decode($cycles, true) ?? [];
        }
        
        if (!is_array($cycles)) {
            return [];
        }
        
        // Filter out empty cycles and reindex to ensure sequential keys
        $processed = array_values(array_filter($cycles, function($cycle) {
            return is_array($cycle) && 
                   !empty($cycle['cycle']) && 
                   isset($cycle['price']) && 
                   is_numeric($cycle['price']);
        }));
        
        return $processed;
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
