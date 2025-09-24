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
        Schema::create('mod_category_mod', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mod_id')->constrained('mod_mods')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('mod_categories')->onDelete('cascade');
            $table->timestamps();
            
            // Prevent duplicates
            $table->unique(['mod_id', 'category_id']);
            
            // Indexes for performance
            $table->index('mod_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_category_mod');
    }
};