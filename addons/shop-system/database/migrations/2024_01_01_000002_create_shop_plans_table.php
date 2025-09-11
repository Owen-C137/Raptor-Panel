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
        Schema::create('shop_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->text('description')->nullable();
            
            // Server Resources (following existing server table structure)
            $table->integer('memory')->default(0);
            $table->integer('swap')->default(0);
            $table->integer('disk')->default(0);
            $table->integer('io')->default(500);
            $table->integer('cpu')->default(0);
            $table->string('threads')->nullable();
            $table->integer('allocation_limit')->nullable();
            $table->integer('database_limit')->nullable();
            $table->integer('backup_limit')->default(0);
            
            // Egg/Nest Configuration
            $table->unsignedInteger('default_egg_id')->nullable();
            $table->json('allowed_eggs')->nullable();
            $table->json('startup_variables')->nullable();
            
            // Billing Configuration
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_hourly', 10, 4)->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0);
            
            // Availability & Limits
            $table->integer('stock_limit')->nullable(); // NULL = unlimited
            $table->integer('max_per_user')->nullable(); // NULL = unlimited
            
            // Node/Location restrictions
            $table->json('allowed_locations')->nullable(); // Array of location IDs
            $table->json('allowed_nodes')->nullable(); // Array of node IDs
            
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('shop_products')->onDelete('cascade');
            $table->foreign('default_egg_id')->references('id')->on('eggs')->onDelete('set null');
            
            $table->index(['product_id', 'status'], 'idx_product_status');
            $table->index(['price_monthly', 'price_hourly'], 'idx_pricing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_plans');
    }
};
