<?php

namespace PterodactylAddons\ShopSystem\Http\View\Composers;

use Illuminate\View\View;
use PterodactylAddons\ShopSystem\Services\ShopConfigService;

class ShopConfigComposer
{
    protected $shopConfig;
    
    public function __construct(ShopConfigService $shopConfig)
    {
        $this->shopConfig = $shopConfig;
    }
    
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $data = [
            'shopConfig' => $this->shopConfig->getShopConfig(),
            'paymentConfig' => $this->shopConfig->getPaymentConfig(),
            'shopEnabled' => $this->shopConfig->isShopEnabled(),
            'enabledPaymentMethods' => $this->shopConfig->getEnabledPaymentMethods(),
        ];

        // Add wallet data if user is authenticated
        if (auth()->check()) {
            try {
                $walletService = app(\PterodactylAddons\ShopSystem\Services\WalletService::class);
                $userWallet = $walletService->getWallet(auth()->id());
                $data['userWallet'] = $userWallet;
            } catch (\Exception $e) {
                $data['userWallet'] = null;
            }
        } else {
            $data['userWallet'] = null;
        }

        $view->with($data);
    }
}
