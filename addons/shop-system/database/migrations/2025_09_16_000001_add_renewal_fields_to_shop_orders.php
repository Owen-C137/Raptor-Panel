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
            $table->boolean('is_renewal')->default(false)->after('payment_method');
            $table->unsignedBigInteger('original_order_id')->nullable()->after('is_renewal');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('setup_fee');
            $table->text('cancellation_reason')->nullable()->after('original_order_id');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            
            $table->foreign('original_order_id')->references('id')->on('shop_orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropForeign(['original_order_id']);
            $table->dropColumn([
                'is_renewal',
                'original_order_id', 
                'discount_amount',
                'cancellation_reason',
                'cancelled_at'
            ]);
        });
    }
};