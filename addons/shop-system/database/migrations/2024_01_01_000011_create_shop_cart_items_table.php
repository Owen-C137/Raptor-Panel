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
        Schema::create('shop_cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Price at time of adding to cart
            $table->decimal('total_price', 10, 2); // unit_price * quantity
            $table->json('product_options')->nullable(); // Store selected options/variants
            $table->json('server_config')->nullable(); // Store server configuration choices
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on('shop_cart')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');
            $table->unique(['cart_id', 'product_id']); // Prevent duplicate items in same cart
            $table->index('cart_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_cart_items');
    }
};
