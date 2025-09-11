<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shop_plans', function (Blueprint $table) {
            // Only remove the truly duplicate egg column
            $table->dropForeign(['default_egg_id']);
            $table->dropColumn('default_egg_id');
            
            // Remove unused columns from original migration that aren't needed
            $table->dropColumn([
                'allowed_eggs',
                'startup_variables',
                'price_monthly',
                'price_hourly', 
                'setup_fee',
                'stock_limit',
                'max_per_user'
            ]);
        });
        
        // IMPORTANT: We're keeping both individual AND JSON columns because:
        // - JSON columns (server_limits, server_feature_limits): Used by admin forms
        // - Individual columns (memory, disk, cpu, etc.): Used by server creation service
        // - The model will sync JSON → individual columns automatically
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_plans', function (Blueprint $table) {
            // Re-add the duplicate egg column
            $table->unsignedInteger('default_egg_id')->nullable();
            $table->foreign('default_egg_id')->references('id')->on('eggs')->onDelete('set null');
            
            // Re-add unused columns
            $table->json('allowed_eggs')->nullable();
            $table->json('startup_variables')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_hourly', 10, 4)->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->integer('stock_limit')->nullable();
            $table->integer('max_per_user')->nullable();
        });
    }
};
