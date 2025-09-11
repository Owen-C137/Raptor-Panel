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
        Schema::create('shop_cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('session_id')->nullable(); // For guest carts
            $table->string('status')->default('active'); // active, abandoned, converted
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->json('metadata')->nullable(); // Store additional cart data
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['session_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_cart');
    }
};
