<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PterodactylAddons\ShopSystem\Http\Controllers\BaseShopController;
use PterodactylAddons\ShopSystem\Models\ShopPlan;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Http\Requests\PlanStoreRequest;
use PterodactylAddons\ShopSystem\Http\Requests\PlanUpdateRequest;
use PterodactylAddons\ShopSystem\Services\ShopConfigService;
use PterodactylAddons\ShopSystem\Services\WalletService;
use PterodactylAddons\ShopSystem\Services\CurrencyService;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\ServerDeletionService;

class PlanController extends BaseShopController
{
    public function __construct(
        ShopConfigService $shopConfigService,
        WalletService $walletService,
        CurrencyService $currencyService
    ) {
        parent::__construct($shopConfigService, $walletService, $currencyService);
    }

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

        // Get available options for import template
        $availableCategories = ShopCategory::where('active', true)->orderBy('name')->pluck('name')->toArray();
        $availableEggs = Egg::with('nest')->orderBy('name')->get()->map(function($egg) {
            return $egg->name;
        })->toArray();
        $availableLocations = Location::orderBy('long')->get()->map(function($location) {
            return $location->long ?: $location->short;
        })->toArray();
        $availableNodes = Node::orderBy('name')->pluck('name')->toArray();

        return $this->view('shop::admin.plans.index', compact(
            'plans', 
            'categories', 
            'availableCategories', 
            'availableEggs', 
            'availableLocations', 
            'availableNodes'
        ));
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
        
        return $this->view('shop::admin.plans.create', compact('categories', 'nodes', 'locations', 'eggs'));
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
        
        return $this->view('shop::admin.plans.show', compact('plan'));
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
        
        return $this->view('shop::admin.plans.edit', compact('plan', 'categories', 'nodes', 'locations', 'eggs', 'limits', 'featureLimits'));
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

    /**
     * Import plans from JSON file
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:json|max:2048',
            'overwrite_existing' => 'sometimes|boolean'
        ]);

        try {
            $file = $request->file('import_file');
            $jsonContent = file_get_contents($file->getPathname());
            $plansData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()
                    ->with('error', 'Invalid JSON file: ' . json_last_error_msg());
            }

            if (!is_array($plansData)) {
                return redirect()->back()
                    ->with('error', 'JSON file must contain an array of plans');
            }

            $imported = 0;
            $errors = [];
            $overwrite = $request->has('overwrite_existing');

            foreach ($plansData as $index => $planData) {
                try {
                    $this->importSinglePlan($planData, $overwrite);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Plan " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            $message = "Successfully imported {$imported} plan(s)";
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }

            return redirect()->route('admin.shop.plans.index')
                ->with($imported > 0 ? 'success' : 'error', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Import a single plan from data array
     */
    private function importSinglePlan(array $data, bool $overwrite = false): void
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new \Exception('Plan name is required');
        }

        if (empty($data['category_name'])) {
            throw new \Exception('Category name is required');
        }

        if (empty($data['billing_cycles']) || !is_array($data['billing_cycles'])) {
            throw new \Exception('At least one billing cycle is required');
        }

        // Find category
        $category = ShopCategory::where('name', $data['category_name'])->first();
        if (!$category) {
            throw new \Exception("Category '{$data['category_name']}' not found");
        }

        // Check if plan exists
        $existingPlan = ShopPlan::where('name', $data['name'])->first();
        if ($existingPlan && !$overwrite) {
            throw new \Exception("Plan '{$data['name']}' already exists");
        }

        // Find egg if specified
        $eggId = null;
        if (!empty($data['egg_name'])) {
            $egg = Egg::where('name', $data['egg_name'])->first();
            if (!$egg) {
                throw new \Exception("Egg '{$data['egg_name']}' not found");
            }
            $eggId = $egg->id;
        }

        // Find locations if specified
        $allowedLocations = [];
        if (!empty($data['allowed_location_names']) && is_array($data['allowed_location_names'])) {
            foreach ($data['allowed_location_names'] as $locationName) {
                $location = Location::where('short', $locationName)
                    ->orWhere('long', $locationName)
                    ->first();
                if ($location) {
                    $allowedLocations[] = $location->id;
                }
            }
        }

        // Find nodes if specified  
        $allowedNodes = [];
        if (!empty($data['allowed_node_names']) && is_array($data['allowed_node_names'])) {
            foreach ($data['allowed_node_names'] as $nodeName) {
                $node = Node::where('name', $nodeName)->first();
                if ($node) {
                    $allowedNodes[] = $node->id;
                }
            }
        }

        // Create or update plan
        $planData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'category_id' => $category->id,
            'sort_order' => $data['sort_order'] ?? 0,
            'visible' => $data['visible'] ?? true,
            'egg_id' => $eggId,
            'server_limits' => json_encode($data['server_limits'] ?? []),
            'server_feature_limits' => json_encode($data['server_feature_limits'] ?? []),
            'allowed_locations' => $allowedLocations ? json_encode($allowedLocations) : null,
            'allowed_nodes' => $allowedNodes ? json_encode($allowedNodes) : null,
        ];

        if ($existingPlan && $overwrite) {
            $existingPlan->update($planData);
            $plan = $existingPlan;
        } else {
            $plan = ShopPlan::create($planData);
        }

        // Handle billing cycles - delete existing and recreate
        if ($existingPlan && $overwrite) {
            $plan->billingCycles()->delete();
        }

        foreach ($data['billing_cycles'] as $cycleData) {
            $plan->billingCycles()->create([
                'cycle' => $cycleData['cycle'] ?? 'monthly',
                'price' => $cycleData['price'] ?? 0,
                'setup_fee' => $cycleData['setup_fee'] ?? 0,
            ]);
        }
    }

    /**
     * Handle batch actions for plans
     */
    public function batchAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'plans' => 'required|array|min:1',
            'plans.*' => 'exists:shop_plans,id',
        ]);

        $action = $request->input('action');
        $planIds = $request->input('plans');
        $count = 0;

        try {
            switch ($action) {
                case 'activate':
                    $count = ShopPlan::whereIn('id', $planIds)->update(['status' => 'active']);
                    break;
                    
                case 'deactivate':
                    $count = ShopPlan::whereIn('id', $planIds)->update(['status' => 'inactive']);
                    break;
                    
                case 'delete':
                    $plans = ShopPlan::whereIn('id', $planIds)->get();
                    foreach ($plans as $plan) {
                        // Delete associated servers if needed
                        $servers = $plan->getAssociatedServers();
                        if ($servers && $servers->count() > 0) {
                            foreach ($servers as $server) {
                                try {
                                    app(ServerDeletionService::class)->handle($server);
                                } catch (\Exception $e) {
                                    // Log error but continue with plan deletion
                                    \Log::error('Failed to delete server during plan batch deletion: ' . $e->getMessage());
                                }
                            }
                        }
                        
                        // Delete billing cycles and plan
                        $plan->billingCycles()->delete();
                        $plan->delete();
                        $count++;
                    }
                    break;
            }

            $actionText = [
                'activate' => 'activated',
                'deactivate' => 'deactivated',
                'delete' => 'deleted'
            ][$action];

            return response()->json([
                'success' => true,
                'message' => "Successfully {$actionText} {$count} plan(s)."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
