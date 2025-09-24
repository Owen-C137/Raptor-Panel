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
        Schema::create('mod_mods', function (Blueprint $table) {
            $table->id();
            $table->integer('curse_mod_id')->unique()->comment('CurseForge mod ID');
            $table->foreignId('game_id')->constrained('mod_games')->onDelete('cascade')->comment('Associated game');
            $table->string('name')->comment('Mod name');
            $table->string('slug')->comment('Mod slug/identifier');
            $table->text('summary')->nullable()->comment('Short description');
            $table->longText('description')->nullable()->comment('Full description');
            
            // Statistics
            $table->bigInteger('download_count')->default(0)->comment('Total downloads');
            $table->integer('popularity_rank')->nullable()->comment('Popularity ranking');
            $table->integer('thumbs_up_count')->default(0)->comment('Thumbs up count');
            $table->decimal('rating', 3, 2)->nullable()->comment('Average rating');
            
            // Media
            $table->string('logo_url', 500)->nullable()->comment('Mod logo URL');
            $table->json('screenshots')->nullable()->comment('Screenshot URLs array');
            
            // Authors (JSON array)
            $table->json('authors')->nullable()->comment('Mod authors information');
            
            // Categories (JSON array of category IDs)
            $table->json('categories')->nullable()->comment('Associated categories');
            
            // Links
            $table->string('website_url', 500)->nullable()->comment('Official website');
            $table->string('wiki_url', 500)->nullable()->comment('Wiki URL');
            $table->string('issues_url', 500)->nullable()->comment('Issues/bug tracker URL');
            $table->string('source_url', 500)->nullable()->comment('Source code URL');
            
            // Dates
            $table->timestamp('date_created')->nullable()->comment('Creation date on CurseForge');
            $table->timestamp('date_modified')->nullable()->comment('Last modified on CurseForge');
            $table->timestamp('date_released')->nullable()->comment('Release date on CurseForge');
            
            // Status
            $table->boolean('allow_mod_distribution')->default(true)->comment('Distribution allowed');
            $table->boolean('is_available')->default(true)->comment('Mod availability');
            $table->integer('game_popularity_rank')->nullable()->comment('Game-specific popularity rank');
            
            // Sync tracking
            $table->timestamp('last_sync_at')->nullable()->comment('Last synchronization timestamp');
            $table->enum('sync_status', ['pending', 'syncing', 'completed', 'failed'])->default('pending')->comment('Sync status');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('curse_mod_id');
            $table->index('game_id');
            $table->index('name');
            $table->index('slug');
            $table->index('download_count', 'idx_download_count_desc'); // Descending order
            $table->index('popularity_rank', 'idx_popularity_rank_asc'); // Ascending order
            $table->index('date_released', 'idx_date_released_desc'); // Descending order
            $table->index('sync_status');
            $table->index(['game_id', 'is_available']);
            $table->index(['game_id', 'download_count']);
            
            // Full-text search index
            $table->fullText(['name', 'summary', 'description'], 'idx_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_mods');
    }
};