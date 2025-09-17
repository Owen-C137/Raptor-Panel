<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for AI messages table.
 * 
 * Stores individual messages within AI conversations.
 * Includes both user messages and AI responses with performance metrics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->onDelete('cascade');
            $table->enum('role', ['user', 'assistant', 'system']); // message sender role
            $table->longText('content'); // message content
            $table->string('model_used', 100)->nullable(); // AI model used for response
            $table->unsignedInteger('tokens_used')->nullable(); // tokens consumed
            $table->unsignedInteger('processing_time_ms')->nullable(); // processing time in milliseconds
            $table->json('metadata')->nullable(); // additional response data (eval_duration, etc.)
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('completed');
            $table->timestamps();

            // Indexes
            $table->index(['conversation_id', 'created_at']);
            $table->index(['role', 'created_at']);
            $table->index('status');
            $table->index('model_used');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};