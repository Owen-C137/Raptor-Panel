<?php

// Migration validation script - compare migrations with actual database structure

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Get current database structure
$currentTables = [
    'shop_categories' => [],
    'shop_plans' => [],
    'user_wallets' => [],
    'wallet_transactions' => [],
    'shop_coupons' => [],
    'shop_orders' => [],
    'shop_payments' => [],
    'shop_coupon_usage' => [],
    'shop_cart' => [],
    'shop_cart_items' => [],
    'shop_settings' => []
];

echo "=== MIGRATION VALIDATION REPORT ===" . PHP_EOL . PHP_EOL;

foreach ($currentTables as $tableName => $columns) {
    echo "Checking table: {$tableName}" . PHP_EOL;
    
    if (!Schema::hasTable($tableName)) {
        echo "  ❌ Table does not exist in database" . PHP_EOL;
        continue;
    }
    
    // Get current table structure
    $dbColumns = DB::select("DESCRIBE {$tableName}");
    $currentStructure = [];
    
    foreach ($dbColumns as $col) {
        $currentStructure[$col->Field] = [
            'type' => $col->Type,
            'null' => $col->Null === 'YES',
            'default' => $col->Default,
            'extra' => $col->Extra
        ];
    }
    
    echo "  ✅ Table exists with " . count($currentStructure) . " columns" . PHP_EOL;
    
    // List all columns for reference
    foreach ($currentStructure as $colName => $colInfo) {
        echo "    - {$colName}: {$colInfo['type']}" . 
             ($colInfo['null'] ? " NULL" : " NOT NULL") . 
             ($colInfo['extra'] ? " {$colInfo['extra']}" : "") . PHP_EOL;
    }
    echo PHP_EOL;
}

// Check foreign key relationships
echo "=== FOREIGN KEY VALIDATION ===" . PHP_EOL;

$foreignKeys = [
    'shop_categories' => ['parent_id' => 'shop_categories.id'],
    'shop_plans' => ['category_id' => 'shop_categories.id', 'egg_id' => 'eggs.id'],
    'user_wallets' => ['user_id' => 'users.id'],
    'wallet_transactions' => ['wallet_id' => 'user_wallets.id'],
    'shop_orders' => ['user_id' => 'users.id', 'plan_id' => 'shop_plans.id', 'server_id' => 'servers.id'],
    'shop_payments' => ['user_id' => 'users.id', 'order_id' => 'shop_orders.id'],
    'shop_coupon_usage' => ['coupon_id' => 'shop_coupons.id', 'user_id' => 'users.id', 'order_id' => 'shop_orders.id'],
    'shop_cart' => ['user_id' => 'users.id'],
    'shop_cart_items' => ['cart_id' => 'shop_cart.id', 'plan_id' => 'shop_plans.id']
];

foreach ($foreignKeys as $table => $fks) {
    echo "Table {$table}:" . PHP_EOL;
    foreach ($fks as $column => $references) {
        if (Schema::hasColumn($table, $column)) {
            echo "  ✅ Column {$column} exists (should reference {$references})" . PHP_EOL;
        } else {
            echo "  ❌ Column {$column} missing (should reference {$references})" . PHP_EOL;
        }
    }
    echo PHP_EOL;
}

echo "=== VALIDATION COMPLETE ===" . PHP_EOL;
