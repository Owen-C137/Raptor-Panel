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
            // Add the JSON columns that the model expects
            $table->json('server_limits')->nullable()->after('description');
            $table->json('server_feature_limits')->nullable()->after('server_limits');
            
            // Add visible column if it doesn't exist
            if (!Schema::hasColumn('shop_plans', 'visible')) {
                $table->boolean('visible')->default(true)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_plans', function (Blueprint $table) {
            $table->dropColumn(['server_limits', 'server_feature_limits']);
            
            if (Schema::hasColumn('shop_plans', 'visible')) {
                $table->dropColumn('visible');
            }
        });
    }
};
