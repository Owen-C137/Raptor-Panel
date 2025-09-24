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
        Schema::create('mod_categories', function (Blueprint $table) {
            $table->id();
            $table->integer('curse_category_id')->unique()->comment('CurseForge category ID');
            $table->foreignId('game_id')->constrained('mod_games')->onDelete('cascade')->comment('Associated game');
            $table->string('name')->comment('Category name');
            $table->string('slug')->comment('Category slug/identifier');
            $table->string('url', 500)->nullable()->comment('Category URL on CurseForge');
            $table->string('icon_url', 500)->nullable()->comment('Category icon URL');
            $table->boolean('is_class')->default(false)->comment('Whether this is a class category');
            $table->integer('class_id')->nullable()->comment('Associated class ID');
            $table->integer('parent_category_id')->nullable()->comment('Parent category ID');
            $table->integer('display_index')->default(0)->comment('Display order index');
            $table->timestamp('date_modified')->nullable()->comment('Last modified on CurseForge');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('curse_category_id');
            $table->index('game_id');
            $table->index('parent_category_id');
            $table->index('is_class');
            $table->index(['game_id', 'parent_category_id']);
            $table->index(['game_id', 'is_class']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_categories');
    }
};