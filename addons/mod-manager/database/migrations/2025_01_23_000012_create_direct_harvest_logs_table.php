<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDirectHarvestLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mod_direct_harvest_logs', function (Blueprint $table) {
            $table->id();
            
            // Session info
            $table->string('session_id')->unique();
            $table->string('session_name');
            $table->enum('harvest_type', ['complete', 'popular', 'recent', 'categories'])->default('complete');
            
            // User tracking (without foreign key constraint to avoid compatibility issues)
            $table->unsignedBigInteger('user_id')->nullable();
            // Note: No foreign key constraint to avoid compatibility issues with existing users table
            
            // Game context
            $table->unsignedBigInteger('game_id');
            $table->foreign('game_id')->references('id')->on('mod_games')->onDelete('cascade');
            
            // Progress tracking
            // Added 'starting' for backward compatibility; controller now sets 'running' directly
            $table->enum('status', ['starting','running', 'completed', 'failed', 'stopped'])->default('running');
            $table->integer('total_mods')->default(0);
            $table->integer('total_files')->default(0);
            $table->integer('processed_mods')->default(0);
            $table->integer('processed_files')->default(0);
            $table->integer('api_calls_made')->default(0);
            
            // Performance metrics
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->decimal('mods_per_second', 8, 2)->nullable();
            
            // Configuration
            $table->json('parameters')->nullable(); // Store limit, phases, etc.
            
            // Results
            $table->integer('new_mods')->default(0);
            $table->integer('updated_mods')->default(0);
            $table->integer('new_files')->default(0);
            $table->integer('updated_files')->default(0);
            
            // Error tracking
            $table->text('error_message')->nullable();
            $table->integer('error_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('session_id');
            $table->index('status');
            $table->index('started_at');
            $table->index(['game_id', 'status']);
            $table->index(['user_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_direct_harvest_logs');
    }
}