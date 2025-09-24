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
        Schema::create('mod_games', function (Blueprint $table) {
            $table->id();
            $table->integer('curse_game_id')->unique()->comment('CurseForge game ID');
            $table->string('name')->comment('Game name');
            $table->string('slug')->comment('Game slug/identifier');
            $table->string('logo_url', 500)->nullable()->comment('Game logo URL');
            $table->string('tile_url', 500)->nullable()->comment('Game tile image URL');
            $table->string('cover_url', 500)->nullable()->comment('Game cover image URL');
            $table->tinyInteger('status')->default(1)->comment('CurseForge status');
            $table->tinyInteger('api_status')->default(1)->comment('API availability status');
            $table->timestamp('date_modified')->nullable()->comment('Last modified on CurseForge');
            $table->boolean('is_active')->default(true)->comment('Whether game is active in our system');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('curse_game_id');
            $table->index('slug');
            $table->index('is_active');
            $table->index(['is_active', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_games');
    }
};