<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for AI insights table.
 * 
 * Stores AI-generated insights and recommendations for the admin panel.
 * Provides actionable intelligence for system management.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->string('insight_type', 50); // recommendation, alert, optimization, trend
            $table->string('category', 50); // performance, security, cost, user_experience
            $table->string('title'); // human-readable insight title
            $table->text('description'); // detailed insight description
            $table->json('data')->nullable(); // supporting data for the insight
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['new', 'acknowledged', 'acted_upon', 'dismissed'])->default('new');
            $table->string('action_url')->nullable(); // URL to take action on this insight
            $table->json('action_data')->nullable(); // data needed for the action
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedInteger('acknowledged_by')->nullable();
            $table->timestamp('expires_at')->nullable(); // when this insight becomes stale
            $table->timestamps();

            // Indexes
            $table->index(['insight_type', 'category']);
            $table->index(['priority', 'status']);
            $table->index('status');
            $table->index('expires_at');
            $table->index('created_at');

            // Foreign key constraints
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};