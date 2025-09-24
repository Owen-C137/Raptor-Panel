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
        Schema::create('mod_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Collection name');
            $table->text('description')->nullable()->comment('Collection description');
            $table->unsignedInteger('user_id')->comment('Collection owner');
            
            // Collection settings
            $table->boolean('is_public')->default(false)->comment('Public visibility');
            $table->boolean('is_featured')->default(false)->comment('Featured collection');
            
            // Metadata
            $table->foreignId('game_id')->constrained('mod_games')->onDelete('cascade')->comment('Target game');
            $table->string('target_version', 100)->nullable()->comment('Target game version');
            $table->string('mod_loader', 50)->nullable()->comment('Required mod loader');
            
            // Collection data
            $table->json('mods')->nullable()->comment('Array of mod IDs with specific versions');
            $table->integer('total_mods')->default(0)->comment('Total mods in collection');
            $table->bigInteger('total_downloads')->default(0)->comment('Total download count');
            
            // Sharing
            $table->string('share_code', 20)->unique()->nullable()->comment('Unique share code');
            $table->integer('download_count')->default(0)->comment('Collection download count');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('game_id');
            $table->index('is_public');
            $table->index('share_code');
            $table->index(['is_public', 'is_featured']);
            $table->index(['game_id', 'is_public']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_collections');
    }
};