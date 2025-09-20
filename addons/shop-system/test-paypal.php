<?php

require_once '/var/www/pterodactyl/vendor/autoload.php';

try {
    // Test PayPal gateway instantiation
    echo "Testing PayPal Gateway...\n";
    
    // Create a mock config service
    $config = [
        'client_id' => 'test',
        'client_secret' => 'test',
        'mode' => 'sandbox',
        'enabled' => true,
    ];
    
    echo "Config: " . json_encode($config) . "\n";
    
    // Test if PayPal SDK is available
    echo "Testing PayPal SDK...\n";
    $environment = new PayPalCheckoutSdk\Core\SandboxEnvironment('test', 'test');
    echo "✅ PayPal SDK loaded successfully\n";
    
    $client = new PayPalCheckoutSdk\Core\PayPalHttpClient($environment);
    echo "✅ PayPal client created successfully\n";
    
    echo "All tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
