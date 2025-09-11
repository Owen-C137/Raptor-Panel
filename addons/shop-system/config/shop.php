<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shop System License
    |--------------------------------------------------------------------------
    | 
    | Your shop system license key. Required for production use.
    | Get your license at: https://pterodactyl-addons.com/shop-system
    |
    */
    'license_key' => env('SHOP_LICENSE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Shop System Status
    |--------------------------------------------------------------------------
    */
    'enabled' => env('SHOP_ENABLED', false),
    'maintenance_mode' => env('SHOP_MAINTENANCE', false),
    'maintenance_message' => env('SHOP_MAINTENANCE_MESSAGE', 'Shop is temporarily unavailable.'),

    /*
    |--------------------------------------------------------------------------
    | Branding & Customization
    |--------------------------------------------------------------------------
    */
    'branding' => [
        'name' => env('SHOP_NAME', 'Server Shop'),
        'logo' => env('SHOP_LOGO', '/assets/img/pterodactyl.svg'),
        'primary_color' => env('SHOP_PRIMARY_COLOR', '#0ea5e9'),
        'custom_css' => env('SHOP_CUSTOM_CSS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    */
    'currency' => [
        'default' => env('SHOP_CURRENCY', 'USD'),
        'symbol' => env('SHOP_CURRENCY_SYMBOL', '$'),
        'precision' => (int) env('SHOP_CURRENCY_PRECISION', 2),
        'position' => env('SHOP_CURRENCY_POSITION', 'before'), // 'before' or 'after'
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'grace_period_hours' => (int) env('SHOP_GRACE_PERIOD', 72),
        'renewal_reminder_days' => array_map('intval', explode(',', env('SHOP_RENEWAL_REMINDER', '7,3,1'))),
        'auto_suspend_after_grace' => env('SHOP_AUTO_SUSPEND', true),
        'auto_terminate_days' => (int) env('SHOP_AUTO_TERMINATE', 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Configuration
    |--------------------------------------------------------------------------
    */
    'wallet' => [
        'enabled' => env('SHOP_WALLET_ENABLED', true),
        'minimum_deposit' => (float) env('SHOP_MINIMUM_DEPOSIT', 5.00),
        'maximum_balance' => (float) env('SHOP_MAXIMUM_BALANCE', 10000.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    */
    'payment_gateways' => [
        'stripe' => [
            'driver' => \PterodactylAddons\ShopSystem\PaymentGateways\StripeGateway::class,
            'name' => 'Credit Card (Stripe)',
            'enabled' => env('STRIPE_ENABLED', false),
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'paypal' => [
            'driver' => \PterodactylAddons\ShopSystem\PaymentGateways\PayPalGateway::class,
            'name' => 'PayPal',
            'enabled' => env('PAYPAL_ENABLED', false),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'sandbox' => env('PAYPAL_SANDBOX', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shop Limits & Restrictions
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_orders_per_user' => env('SHOP_MAX_ORDERS_PER_USER', null),
        'max_pending_orders' => (int) env('SHOP_MAX_PENDING_ORDERS', 3),
        'order_timeout_minutes' => (int) env('SHOP_ORDER_TIMEOUT', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Configuration
    |--------------------------------------------------------------------------
    */
    'servers' => [
        'auto_start_after_install' => env('SHOP_AUTO_START_SERVERS', true),
        'install_timeout_minutes' => (int) env('SHOP_INSTALL_TIMEOUT', 10),
        'default_startup_timeout' => (int) env('SHOP_STARTUP_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Configuration
    |--------------------------------------------------------------------------
    */
    'tax' => [
        'enabled' => env('SHOP_TAX_ENABLED', false),
        'inclusive' => env('SHOP_TAX_INCLUSIVE', false), // Whether prices include tax
        'calculate_by_ip' => env('SHOP_TAX_BY_IP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Referral System
    |--------------------------------------------------------------------------
    */
    'referrals' => [
        'enabled' => env('SHOP_REFERRALS_ENABLED', false),
        'default_commission_rate' => (float) env('SHOP_REFERRAL_RATE', 0.10), // 10%
        'minimum_payout' => (float) env('SHOP_REFERRAL_MIN_PAYOUT', 25.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'email' => [
            'enabled' => env('SHOP_EMAIL_NOTIFICATIONS', true),
            'from_address' => env('SHOP_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
            'from_name' => env('SHOP_EMAIL_FROM_NAME', env('SHOP_NAME', 'Server Shop')),
        ],
        'discord' => [
            'enabled' => env('SHOP_DISCORD_NOTIFICATIONS', false),
            'webhook_url' => env('SHOP_DISCORD_WEBHOOK'),
            'mention_roles' => explode(',', env('SHOP_DISCORD_MENTION_ROLES', '')),
        ],
        'renewal_reminders' => [
            'enabled' => env('SHOP_RENEWAL_REMINDERS', true),
            'days_before' => array_map('intval', explode(',', env('SHOP_RENEWAL_REMINDER_DAYS', '7,3,1'))),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Job Configuration
    |--------------------------------------------------------------------------
    */
    'jobs' => [
        'queue' => env('SHOP_QUEUE_NAME', 'default'),
        'retry_attempts' => (int) env('SHOP_JOB_RETRIES', 3),
        'batch_size' => (int) env('SHOP_JOB_BATCH_SIZE', 50),
        'processing_timeout' => (int) env('SHOP_JOB_TIMEOUT', 300), // 5 minutes
        'schedule' => [
            'renewals' => env('SHOP_RENEWAL_SCHEDULE', '0 3 * * *'), // Daily at 3 AM
            'suspensions' => env('SHOP_SUSPENSION_SCHEDULE', '0 4 * * *'), // Daily at 4 AM
            'terminations' => env('SHOP_TERMINATION_SCHEDULE', '0 5 * * *'), // Daily at 5 AM
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Anti-Fraud
    |--------------------------------------------------------------------------
    */
    'security' => [
        'payment_rate_limit' => (int) env('SHOP_PAYMENT_RATE_LIMIT', 3), // Per minute
        'require_email_verification' => env('SHOP_REQUIRE_EMAIL_VERIFICATION', true),
        'block_vpn_payments' => env('SHOP_BLOCK_VPN_PAYMENTS', false),
    ],
];
 