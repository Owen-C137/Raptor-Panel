<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use PterodactylAddons\ShopSystem\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Repositories\ShopCategoryRepository;
use PterodactylAddons\ShopSystem\Repositories\ShopOrderRepository;
use PterodactylAddons\ShopSystem\Repositories\UserWalletRepository;
use PterodactylAddons\ShopSystem\Services\ShopOrderService;
use PterodactylAddons\ShopSystem\Services\WalletService;
use PterodactylAddons\ShopSystem\Transformers\ShopOrderTransformer;
use PterodactylAddons\ShopSystem\Transformers\WalletTransformer;

/**
 * RESTful API controller for external shop integration.
 * Provides endpoints for third-party applications and integrations.
 */
class ApiController extends Controller
{
    public function __construct(
        private ShopCategoryRepository $categoryRepository,
        private ShopOrderRepository $orderRepository,
        private UserWalletRepository $walletRepository,
        private ShopOrderService $orderService,
        private WalletService $walletService
    ) {
        // API endpoints require API key authentication
        $this->middleware('auth:sanctum');
    }

    /**
     * Get shop products with filtering and pagination.
     */
    public function getProducts(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'visible' => 'nullable|boolean',
            'enabled' => 'nullable|boolean',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $filters = $request->only(['type', 'visible', 'enabled']);
        $categories = $this->categoryRepository->getVisibleCategories(
            filters: $filters,
            perPage: $request->integer('per_page', 20)
        );

        return $this->successResponse([
            'categories' => $categories->items(),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ],
        ]);
    }

    /**
     * Get specific category by ID or UUID.
     */
    public function getCategory(string $identifier): JsonResponse
    {
        $category = is_numeric($identifier)
            ? $this->categoryRepository->find($identifier)
            : $this->categoryRepository->findByUuid($identifier);

        if (!$category || $category->status !== 'active') {
            return $this->errorResponse('Category not found', 404);
        }

        return $this->successResponse([
            'category' => $category,
        ]);
    }

    /**
     * Get user's orders with filtering.
     */
    public function getUserOrders(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,active,suspended,cancelled,terminated',
            'search' => 'nullable|string|max:255',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $filters = $request->only(['status', 'search']);
        $orders = $this->orderRepository->getByUser(
            userId: auth()->id(),
            filters: $filters,
            perPage: $request->integer('per_page', 20)
        );

        return $this->successResponse([
            'orders' => ShopOrderTransformer::collection($orders->items()),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Get specific order by ID or UUID.
     */
    public function getOrder(string $identifier): JsonResponse
    {
        $order = is_numeric($identifier)
            ? $this->orderRepository->find($identifier)
            : $this->orderRepository->findByUuid($identifier);

        if (!$order || $order->user_id !== auth()->id()) {
            return $this->errorResponse('Order not found', 404);
        }

        return $this->successResponse([
            'order' => ShopOrderTransformer::make($order),
        ]);
    }

    /**
     * Create a new order via API.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:shop_plans,id',
            'quantity' => 'integer|min:1|max:10',
            'coupon_code' => 'nullable|string|max:50',
            'payment_method' => 'required|string|in:wallet,stripe,paypal',
        ]);

        try {
            $plan = \PterodactylAddons\ShopSystem\Models\Shop\ShopPlan::find($request->plan_id);
            
            if (!$plan || !$plan->isAvailable()) {
                return $this->errorResponse('Selected plan is not available', 400);
            }

            $quantity = $request->integer('quantity', 1);
            $cartItems = [[
                'plan' => $plan,
                'quantity' => $quantity,
                'price' => $plan->price,
                'setup_fee' => $plan->setup_fee,
                'subtotal' => $plan->price * $quantity,
                'setup_total' => $plan->setup_fee * $quantity,
            ]];

            // Apply coupon if provided
            $coupon = null;
            if ($request->filled('coupon_code')) {
                $coupon = \PterodactylAddons\ShopSystem\Repositories\ShopCouponRepository::findByCode($request->coupon_code);
                if (!$coupon || !$coupon->isValid()) {
                    return $this->errorResponse('Invalid coupon code', 400);
                }
            }

            // Calculate totals
            $subtotal = $cartItems[0]['subtotal'] + $cartItems[0]['setup_total'];
            $discount = $coupon ? $coupon->calculateDiscount($subtotal) : 0;
            $total = max(0, $subtotal - $discount);

            $totals = [
                'subtotal' => $cartItems[0]['subtotal'],
                'setup_total' => $cartItems[0]['setup_total'],
                'discount' => $discount,
                'tax' => 0,
                'total' => $total,
            ];

            // Create order
            $order = $this->orderService->createOrder([
                'user_id' => auth()->id(),
                'items' => $cartItems,
                'totals' => $totals,
                'coupon' => $coupon,
                'payment_method' => $request->payment_method,
            ]);

            $this->logActivity('Order created via API', $order, [
                'order_id' => $order->id,
                'plan_id' => $plan->id,
                'quantity' => $quantity,
                'total' => $total,
            ]);

            return $this->successResponse([
                'order' => ShopOrderTransformer::make($order),
            ], 'Order created successfully', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel an order via API.
     */
    public function cancelOrder(string $identifier): JsonResponse
    {
        $order = is_numeric($identifier)
            ? $this->orderRepository->find($identifier)
            : $this->orderRepository->findByUuid($identifier);

        if (!$order || $order->user_id !== auth()->id()) {
            return $this->errorResponse('Order not found', 404);
        }

        if (!$order->canBeCancelled()) {
            return $this->errorResponse('Order cannot be cancelled', 400);
        }

        try {
            $this->orderService->cancelOrder($order->id, 'Cancelled via API');

            $this->logActivity('Order cancelled via API', $order, [
                'order_id' => $order->id,
            ]);

            return $this->successResponse([
                'order' => ShopOrderTransformer::make($order->fresh()),
            ], 'Order cancelled successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user's wallet information.
     */
    public function getWallet(): JsonResponse
    {
        $wallet = $this->walletService->getWallet(auth()->id());
        
        return $this->successResponse([
            'wallet' => WalletTransformer::make($wallet),
        ]);
    }

    /**
     * Get wallet transaction history.
     */
    public function getWalletTransactions(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string|in:credit,debit',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $filters = $request->only(['type', 'from_date', 'to_date']);
        $transactions = $this->walletRepository->getTransactions(
            userId: auth()->id(),
            filters: $filters,
            perPage: $request->integer('per_page', 20)
        );

        return $this->successResponse([
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Add funds to wallet via API (requires external payment processing).
     */
    public function addWalletFunds(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:' . config('shop.wallet.minimum_deposit', 5.00),
                'max:1000',
            ],
            'payment_reference' => 'required|string|max:255',
            'payment_method' => 'required|string|max:50',
        ]);

        $amount = $request->amount;
        $currentBalance = $this->walletService->getBalance(auth()->user());
        $maxBalance = config('shop.wallet.maximum_balance', 10000.00);

        if ($currentBalance + $amount > $maxBalance) {
            return $this->errorResponse("Adding this amount would exceed the maximum wallet balance", 400);
        }

        try {
            $result = $this->walletService->credit(
                userId: auth()->id(),
                amount: $amount,
                description: "API deposit - " . $request->payment_reference,
                metadata: [
                    'payment_reference' => $request->payment_reference,
                    'payment_method' => $request->payment_method,
                    'api_source' => true,
                ]
            );

            if ($result) {
                $this->logActivity('Wallet funded via API', null, [
                    'amount' => $amount,
                    'payment_reference' => $request->payment_reference,
                    'payment_method' => $request->payment_method,
                ]);

                return $this->successResponse([
                    'wallet' => WalletTransformer::make($this->walletService->getWallet(auth()->id())),
                ], 'Funds added successfully');
            } else {
                return $this->errorResponse('Failed to add funds', 500);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add funds: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get shop statistics for the authenticated user.
     */
    public function getStatistics(): JsonResponse
    {
        $userId = auth()->id();
        
        $stats = [
            'orders' => [
                'total' => $this->orderRepository->countByUser($userId),
                'active' => $this->orderRepository->countByUser($userId, 'active'),
                'pending' => $this->orderRepository->countByUser($userId, 'pending'),
                'cancelled' => $this->orderRepository->countByUser($userId, 'cancelled'),
            ],
            'wallet' => [
                'balance' => $this->walletService->getBalance($userId),
                'total_spent' => $this->walletRepository->getTotalSpent($userId),
                'total_deposited' => $this->walletRepository->getTotalDeposited($userId),
            ],
            'recent_activity' => [
                'recent_orders' => $this->orderRepository->getRecentByUser($userId, 5),
                'recent_transactions' => $this->walletRepository->getRecentTransactions($userId, 5),
            ],
        ];

        return $this->successResponse($stats);
    }

    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        return $this->successResponse([
            'status' => 'healthy',
            'shop_enabled' => config('shop.enabled', false),
            'maintenance_mode' => config('shop.maintenance_mode', false),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
