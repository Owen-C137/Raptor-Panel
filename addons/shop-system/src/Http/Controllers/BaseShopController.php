<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers;

use Pterodactyl\Http\Controllers\Controller as BaseController;
use PterodactylAddons\ShopSystem\Services\ShopConfigService;
use PterodactylAddons\ShopSystem\Services\WalletService;
use Illuminate\View\View;

abstract class BaseShopController extends BaseController
{
    protected ShopConfigService $shopConfigService;
    protected WalletService $walletService;

    public function __construct()
    {
        $this->shopConfigService = app(ShopConfigService::class);
        $this->walletService = app(WalletService::class);
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
            } catch (\Exception $e) {
                $userWallet = null;
            }
        }

        // Merge shop data with provided data
        $shopData = [
            'shopConfig' => $shopConfig,
            'paymentConfig' => $paymentConfig,
            'shopEnabled' => $shopEnabled,
            'enabledPaymentMethods' => $enabledPaymentMethods,
            'userWallet' => $userWallet,
        ];

        return view($view, array_merge($shopData, $data));
    }
}