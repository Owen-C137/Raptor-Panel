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
        Schema::create('shop_coupons', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Discount Configuration
            $table->enum('type', ['percentage', 'fixed_amount']);
            $table->decimal('value', 10, 2); // Percentage (0-100) or fixed amount
            $table->json('applicable_plans')->nullable(); // Plan IDs this coupon applies to
            
            // Usage Limits
            $table->integer('usage_limit')->nullable(); // null = unlimited
            $table->integer('usage_limit_per_user')->nullable();
            $table->integer('used_count')->default(0);
            
            // Validity
            $table->boolean('active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            
            // Minimum Requirements
            $table->decimal('minimum_amount', 10, 2)->nullable();
            $table->boolean('first_order_only')->default(false);
            
            $table->timestamps();
            
            $table->index(['code', 'active'], 'idx_code_active');
            $table->index(['active', 'valid_from', 'valid_until'], 'idx_validity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_coupons');
    }
};
