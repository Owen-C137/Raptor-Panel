<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for AI analysis results table.
 * 
 * Stores AI-generated analysis results for servers, logs, performance, etc.
 * Used for caching insights and providing historical analytics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analysis_results', function (Blueprint $table) {
            $table->id();
            $table->string('analysis_type', 50); // performance, security, logs, resource, etc.
            $table->string('subject_type', 50); // server, node, user, system
            $table->unsignedInteger('subject_id')->nullable(); // ID of the analyzed subject
            $table->unsignedInteger('user_id')->nullable(); // user who requested analysis
            $table->string('model_used', 100); // AI model used
            $table->json('input_data'); // data that was analyzed
            $table->longText('analysis_result'); // AI-generated analysis
            $table->json('insights')->nullable(); // structured insights/recommendations
            $table->decimal('confidence_score', 3, 2)->nullable(); // AI confidence level (0-1)
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->timestamp('expires_at')->nullable(); // when this analysis expires
            $table->timestamps();

            // Indexes
            $table->index(['analysis_type', 'subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index('expires_at');
            $table->index('created_at');

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_results');
    }
};