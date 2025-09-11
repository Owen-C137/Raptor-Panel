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
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('type', ['server', 'addon', 'resource'])->default('server');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->json('server_config')->nullable(); // Server creation configuration
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('shop_categories')->onDelete('set null');
            $table->index(['type', 'status'], 'idx_type_status');
            $table->index(['category_id', 'status']);
            $table->index('sort_order', 'idx_sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_products');
    }
};
