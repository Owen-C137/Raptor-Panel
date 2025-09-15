<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers;

use Pterodactyl\Http\Controllers\Controller as BaseController;
use PterodactylAddons\ShopSystem\Services\ShopConfigService;
use PterodactylAddons\ShopSystem\Services\WalletService;
use PterodactylAddons\ShopSystem\Services\CurrencyService;
use Illuminate\View\View;

abstract class BaseShopController extends BaseController
{
    protected ShopConfigService $shopConfigService;
    protected WalletService $walletService;
    protected CurrencyService $currencyService;

    public function __construct(
        ShopConfigService $shopConfigService,
        WalletService $walletService,
        CurrencyService $currencyService
    ) {
        $this->shopConfigService = $shopConfigService;
        $this->walletService = $walletService;
        $this->currencyService = $currencyService;
    }

    /**
     * Create a view with shop configuration data automatically included
     */
    protected function view(string $view, array $data = []): View
    {
        // Get shop configuration
        $shopConfig = $this->shopConfigService->getShopConfig();
        $paymentConfig = $this->shopConfigService->getPaymentConfig();
        $shopEnabled = $this->shopConfigService->isShopEnabled();
        $enabledPaymentMethods = $this->shopConfigService->getEnabledPaymentMethods();

        // Get user wallet if authenticated
        $userWallet = null;
        if (auth()->check()) {
            try {
                $userWallet = $this->walletService->getWallet(auth()->id());
                \Log::info('BaseShopController: Wallet loaded successfully', ['balance' => $userWallet->balance ?? 'null']);
            } catch (\Exception $e) {
                \Log::error('BaseShopController: Failed to load wallet', ['error' => $e->getMessage()]);
                $userWallet = null;
            }
        } else {
            \Log::info('BaseShopController: User not authenticated, wallet not loaded');
        }

        // Merge shop data with provided data
        $shopData = [
            'shopConfig' => $shopConfig,
            'paymentConfig' => $paymentConfig,
            'shopEnabled' => $shopEnabled,
            'enabledPaymentMethods' => $enabledPaymentMethods,
            'userWallet' => $userWallet,
            'currencySymbol' => $this->currencyService->getCurrentCurrencySymbol(),
            'currencyCode' => $this->currencyService->getCurrentCurrency(),
        ];

        return view($view, array_merge($shopData, $data));
    }

    /**
     * Get the authenticated user.
     */
    protected function user()
    {
        return auth()->user();
    }

    /**
     * Check if shop is enabled and not in maintenance mode.
     */
    protected function checkShopAvailability(): void
    {
        if (!config('shop.enabled')) {
            abort(503, config('shop.maintenance_message', 'Shop is temporarily unavailable.'));
        }
    }

    /**
     * Format currency amount.
     */
    protected function formatCurrency(float $amount): string
    {
        $symbol = $this->currencyService->getCurrentCurrencySymbol();
        $precision = config('shop.currency.precision', 2);
        $position = config('shop.currency.position', 'before');

        $formatted = number_format($amount, $precision);

        return $position === 'before' ? $symbol . $formatted : $formatted . $symbol;
    }

    /**
     * Generate success response for API endpoints.
     */
    protected function successResponse($data = null, string $message = 'Success', int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Generate error response for API endpoints.
     */
    protected function errorResponse(string $message = 'An error occurred', int $status = 400, $errors = null): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Log user activity for shop actions.
     */
    protected function logActivity(string $description, $subject = null, array $properties = []): void
    {
        if (function_exists('activity') && $this->user()) {
            activity()
                ->causedBy($this->user())
                ->performedOn($subject)
                ->withProperties($properties)
                ->log($description);
        }
    }
}