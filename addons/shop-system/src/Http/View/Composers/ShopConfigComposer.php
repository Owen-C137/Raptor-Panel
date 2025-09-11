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
        $view->with([
            'shopConfig' => $this->shopConfig->getShopConfig(),
            'paymentConfig' => $this->shopConfig->getPaymentConfig(),
            'shopEnabled' => $this->shopConfig->isShopEnabled(),
            'enabledPaymentMethods' => $this->shopConfig->getEnabledPaymentMethods(),
        ]);
    }
}
