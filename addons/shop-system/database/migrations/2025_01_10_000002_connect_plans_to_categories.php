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
        Schema::table('shop_plans', function (Blueprint $table) {
            // First, drop the foreign key constraint on product_id
            $table->dropForeign(['product_id']);
            
            // Add category_id column
            $table->unsignedBigInteger('category_id')->nullable()->after('uuid');
            
            // Add foreign key for category_id
            $table->foreign('category_id')->references('id')->on('shop_categories')->onDelete('cascade');
            
            // We'll keep product_id for now to allow data migration, but make it nullable
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_plans', function (Blueprint $table) {
            // Remove category foreign key and column
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            
            // Restore product_id as required
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');
        });
    }
};
