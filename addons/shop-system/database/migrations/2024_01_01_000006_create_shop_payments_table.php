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
        Schema::create('shop_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable(); // Can be null for wallet top-ups
            
            // Payment Details
            $table->enum('type', ['order_payment', 'renewal', 'setup_fee', 'wallet_topup', 'refund']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded']);
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('USD');
            
            // Gateway Information
            $table->string('gateway'); // stripe, paypal, manual, wallet
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_metadata')->nullable(); // Store gateway response data
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('shop_orders')->onDelete('set null');
            
            $table->index(['user_id', 'status'], 'idx_user_payment_status');
            $table->index(['gateway', 'gateway_transaction_id'], 'idx_gateway_transaction');
            $table->index(['type', 'status', 'created_at'], 'idx_payment_type_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_payments');
    }
};
