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
        Schema::table('shop_orders', function (Blueprint $table) {
            // Add billing details JSON field
            $table->json('billing_details')->nullable()->after('server_config');
            
            // Add payment method for renewals
            $table->string('payment_method')->nullable()->after('billing_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropColumn(['billing_details', 'payment_method']);
        });
    }
};
