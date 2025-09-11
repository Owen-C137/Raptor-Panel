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
        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name'); // Store name at time of purchase
            $table->text('product_description')->nullable(); // Store description at time of purchase
            $table->decimal('unit_price', 10, 2); // Price per item at time of purchase
            $table->integer('quantity');
            $table->decimal('total_price', 10, 2); // unit_price * quantity
            $table->json('product_data')->nullable(); // Store full product data at time of purchase
            $table->json('server_data')->nullable(); // Store server configuration data
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('shop_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('restrict');
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_order_items');
    }
};
