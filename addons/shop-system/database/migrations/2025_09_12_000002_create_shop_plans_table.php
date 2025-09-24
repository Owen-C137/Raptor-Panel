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
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedInteger('egg_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('visible')->default(true);
            $table->longText('server_limits')->nullable();
            $table->longText('server_feature_limits')->nullable();
            $table->integer('memory')->default(0);
            $table->integer('swap')->default(0);
            $table->integer('disk')->default(0);
            $table->integer('io')->default(500);
            $table->integer('cpu')->default(0);
            $table->string('threads')->nullable();
            $table->integer('allocation_limit')->nullable();
            $table->integer('database_limit')->nullable();
            $table->integer('backup_limit')->default(0);
            $table->longText('allowed_locations')->nullable();
            $table->longText('allowed_nodes')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->longText('billing_cycles');
            $table->timestamps();

            $table->index(['category_id', 'visible', 'status']);
            $table->index('egg_id');
            $table->foreign('category_id')->references('id')->on('shop_categories')->onDelete('set null');
            $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('set null');
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
