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
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('plan_id');
            $table->unsignedInteger('server_id')->nullable(); // Linked after provisioning
            
            // Order Details
            $table->enum('status', ['pending', 'processing', 'active', 'suspended', 'cancelled', 'terminated'])->default('pending');
            $table->enum('billing_cycle', ['hourly', 'monthly', 'quarterly', 'semi_annually', 'annually', 'one_time']);
            
            // Pricing (stored to maintain history)
            $table->decimal('amount', 10, 2);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            
            // Billing Dates
            $table->timestamp('next_due_at')->nullable();
            $table->timestamp('last_renewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            
            // Configuration snapshot (for consistency)
            $table->json('server_config'); // Memory, CPU, etc at order time
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('shop_plans');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('set null');
            
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['billing_cycle', 'next_due_at'], 'idx_billing_due');
            $table->index(['status', 'next_due_at', 'expires_at'], 'idx_status_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_orders');
    }
};
