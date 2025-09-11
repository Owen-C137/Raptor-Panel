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
        // Remove product_id from shop_plans table
        Schema::table('shop_plans', function (Blueprint $table) {
            if (Schema::hasColumn('shop_plans', 'product_id')) {
                $table->dropColumn('product_id');
            }
        });

        // Drop product-related tables that may have been created
        Schema::dropIfExists('shop_cart_items');
        Schema::dropIfExists('shop_order_items'); 
        Schema::dropIfExists('shop_products');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create products table if needed (for rollback)
        if (!Schema::hasTable('shop_products')) {
            Schema::create('shop_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('category_id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->boolean('visible')->default(true);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('shop_categories')->onDelete('cascade');
                $table->index(['category_id', 'visible']);
                $table->index('sort_order');
            });
        }

        // Re-add product_id to shop_plans
        Schema::table('shop_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('shop_plans', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable();
                $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');
            }
        });
    }
};
