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
            $table->unsignedInteger('server_id')->nullable();
            $table->enum('status', ['pending', 'processing', 'active', 'suspended', 'cancelled', 'terminated'])->default('pending');
            $table->enum('billing_cycle', ['hourly', 'monthly', 'quarterly', 'semi_annually', 'annually', 'one_time']);
            $table->decimal('amount', 10, 2);
            $table->decimal('setup_fee', 10, 2)->default(0.00);
            $table->char('currency', 3)->default('USD');
            $table->timestamp('next_due_at')->nullable();
            $table->timestamp('last_renewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->longText('server_config');
            $table->longText('billing_details')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['plan_id', 'status']);
            $table->index('server_id');
            $table->index('next_due_at');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('shop_plans')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('set null');
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
