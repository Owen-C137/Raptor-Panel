<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Server;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use PterodactylAddons\ShopSystem\Http\Controllers\BaseShopController;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPlan;
use PterodactylAddons\ShopSystem\Services\ShopConfigService;
use PterodactylAddons\ShopSystem\Services\WalletService;
use PterodactylAddons\ShopSystem\Services\CurrencyService;
use PterodactylAddons\ShopSystem\Services\ShopOrderService;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PlanController extends BaseShopController
{
    public function __construct(
        private ShopOrderService $orderService,
        ShopConfigService $shopConfigService,
        WalletService $walletService,
        CurrencyService $currencyService
    ) {
        parent::__construct($shopConfigService, $walletService, $currencyService);
    }

    /**
     * Display server plan management page
     */
    public function index(Request $request, string $server): View
    {
        // Find the server
        $serverModel = Server::where('uuidShort', $server)
            ->orWhere('uuid', $server)
            ->firstOrFail();

        // Check if user has permission to access this server
        if (!Gate::allows('view-server', $serverModel)) {
            abort(403, 'You do not have permission to access this server.');
        }
        
        // Check if user has active plan access (skip for server owner)
        if ($serverModel->owner_id !== Auth::id()) {
            $activePlan = ShopOrder::where('server_id', $serverModel->id)
                ->where('user_id', Auth::id())
                ->where('status', ShopOrder::STATUS_ACTIVE)
                ->exists();
                
            if (!$activePlan) {
                return redirect()->route('shop.index')
                    ->with('error', 'You need an active plan to access this server.');
            }
        }

        // Find the current plan for this server
        $currentOrder = ShopOrder::where('server_id', $serverModel->id)
            ->where('user_id', Auth::id())
            ->where('status', ShopOrder::STATUS_ACTIVE)
            ->with(['plan', 'plan.category'])
            ->latest()
            ->first();

        // If no active order found, show message
        if (!$currentOrder) {
            return $this->view('shop::server.plan.no-plan', [
                'server' => $serverModel,
            ]);
        }

        // Get plan details
        $plan = $currentOrder->plan;
        $category = $plan->category;

        // Calculate expiration date
        $expirationDate = null;
        if ($plan->billing_cycle !== 'one_time' && $currentOrder->created_at) {
            $expirationDate = $this->calculateExpirationDate($currentOrder->created_at, $plan->billing_cycle);
        }

        // Check if plan is near expiration (within 7 days)
        $isNearExpiration = $expirationDate && $expirationDate->diffInDays(Carbon::now()) <= 7 && $expirationDate->isFuture();

        // Check if plan is expired
        $isExpired = $expirationDate && $expirationDate->isPast();

        // FOR TESTING: Force renewal options to show (remove this in production)
        $forceTestRenewal = $request->get('test_renewal') === 'true';
        if ($forceTestRenewal) {
            $isNearExpiration = true;
        }

        // Get renewal options
        $renewalOptions = $this->getRenewalOptions($plan);

        return $this->view('shop::server.plan.manage', [
            'server' => $serverModel,
            'currentOrder' => $currentOrder,
            'plan' => $plan,
            'category' => $category,
            'expirationDate' => $expirationDate,
            'isNearExpiration' => $isNearExpiration,
            'isExpired' => $isExpired,
            'renewalOptions' => $renewalOptions,
        ]);
    }

    /**
     * Renew the current plan
     */
    public function renew(Request $request, string $server): JsonResponse
    {
        $request->validate([
            'billing_cycle' => 'required|in:monthly,quarterly,semi_annually,annually',
        ]);

        // Find the server
        $serverModel = Server::where('uuidShort', $server)
            ->orWhere('uuid', $server)
            ->firstOrFail();

        // Check permissions
        if (!Gate::allows('view-server', $serverModel)) {
            abort(403, 'You do not have permission to access this server.');
        }

        // Find current order
        $currentOrder = ShopOrder::where('server_id', $serverModel->id)
            ->where('user_id', Auth::id())
            ->where('status', ShopOrder::STATUS_ACTIVE)
            ->latest()
            ->first();

        if (!$currentOrder) {
            return response()->json(['error' => 'No active plan found for this server.'], 404);
        }

        try {
            // Create renewal order
            $renewalOrder = $this->orderService->createRenewalOrder(
                $currentOrder,
                $request->billing_cycle
            );

            return response()->json([
                'success' => true,
                'order_id' => $renewalOrder->id,
                'message' => 'Renewal order created successfully.',
                'redirect_url' => route('shop.checkout', ['order' => $renewalOrder->id])
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel the current plan
     */
    public function cancel(Request $request, string $server): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'confirm' => 'required|accepted',
        ]);

        // Find the server
        $serverModel = Server::where('uuidShort', $server)
            ->orWhere('uuid', $server)
            ->firstOrFail();

        // Check permissions
        if (!Gate::allows('view-server', $serverModel)) {
            abort(403, 'You do not have permission to access this server.');
        }

        // Find current order
        $currentOrder = ShopOrder::where('server_id', $serverModel->id)
            ->where('user_id', Auth::id())
            ->where('status', ShopOrder::STATUS_ACTIVE)
            ->latest()
            ->first();

        if (!$currentOrder) {
            return response()->json(['error' => 'No active plan found for this server.'], 404);
        }

        try {
            // Mark order as cancelled
            $currentOrder->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->reason,
                'cancelled_at' => Carbon::now(),
            ]);

            // Revoke server access for non-owner users
            if ($serverModel->owner_id !== Auth::id()) {
                // Remove user as subuser if they are one
                $subuser = Subuser::where('server_id', $serverModel->id)
                    ->where('user_id', Auth::id())
                    ->first();
                
                if ($subuser) {
                    $subuser->delete();
                }
                
                // Note: If user is the owner, we don't revoke access as they own the server
            }

            // Log the cancellation using Laravel's built-in logging
            try {
                Log::info('Plan cancelled by user', [
                    'user_id' => Auth::id(),
                    'server_id' => $serverModel->id,
                    'order_id' => $currentOrder->id,
                    'reason' => $request->reason,
                    'cancelled_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Continue if logging fails - don't break the cancellation
                Log::warning('Logging failed during plan cancellation: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Plan has been cancelled successfully. Server access has been revoked.',
                'redirect_url' => route('index'), // Redirect to main panel
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get plan usage statistics
     */
    public function usage(Request $request, string $server): JsonResponse
    {
        // Find the server
        $serverModel = Server::where('uuidShort', $server)
            ->orWhere('uuid', $server)
            ->firstOrFail();

        // Check permissions
        if (!Gate::allows('view-server', $serverModel)) {
            abort(403, 'You do not have permission to access this server.');
        }

        // Get server resource usage
        $usage = [
            'memory' => [
                'used' => $serverModel->memory ?? 0,
                'limit' => $serverModel->memory ?? 0,
                'percentage' => 0,
            ],
            'disk' => [
                'used' => $serverModel->disk ?? 0,
                'limit' => $serverModel->disk ?? 0,
                'percentage' => 0,
            ],
            'cpu' => [
                'used' => $serverModel->cpu ?? 0,
                'limit' => $serverModel->cpu ?? 0,
                'percentage' => 0,
            ],
            'databases' => [
                'used' => $serverModel->databases()->count(),
                'limit' => $serverModel->database_limit ?? 0,
                'percentage' => $serverModel->database_limit > 0 ? 
                    ($serverModel->databases()->count() / $serverModel->database_limit * 100) : 0,
            ],
            'allocations' => [
                'used' => $serverModel->allocations()->count(),
                'limit' => $serverModel->allocation_limit ?? 0,
                'percentage' => $serverModel->allocation_limit > 0 ? 
                    ($serverModel->allocations()->count() / $serverModel->allocation_limit * 100) : 0,
            ],
        ];

        return response()->json(['usage' => $usage]);
    }

    /**
     * Calculate expiration date based on billing cycle
     */
    private function calculateExpirationDate(Carbon $startDate, string $billingCycle): ?Carbon
    {
        switch ($billingCycle) {
            case 'monthly':
                return $startDate->copy()->addMonth();
            case 'quarterly':
                return $startDate->copy()->addMonths(3);
            case 'semi_annually':
                return $startDate->copy()->addMonths(6);
            case 'annually':
                return $startDate->copy()->addYear();
            default:
                return null;
        }
    }

    /**
     * Get renewal options for the plan
     */
    private function getRenewalOptions(ShopPlan $plan): array
    {
        $options = [];
        
        if ($plan->monthly_price && $plan->monthly_price > 0) {
            $options['monthly'] = [
                'label' => 'Monthly',
                'price' => $plan->monthly_price,
                'cycle' => 'monthly'
            ];
        }

        if ($plan->quarterly_price && $plan->quarterly_price > 0) {
            $options['quarterly'] = [
                'label' => 'Quarterly (3 months)',
                'price' => $plan->quarterly_price,
                'cycle' => 'quarterly'
            ];
        }

        if ($plan->semi_annually_price && $plan->semi_annually_price > 0) {
            $options['semi_annually'] = [
                'label' => 'Semi-Annually (6 months)',
                'price' => $plan->semi_annually_price,
                'cycle' => 'semi_annually'
            ];
        }

        if ($plan->annually_price && $plan->annually_price > 0) {
            $options['annually'] = [
                'label' => 'Annually',
                'price' => $plan->annually_price,
                'cycle' => 'annually'
            ];
        }

        return $options;
    }
}