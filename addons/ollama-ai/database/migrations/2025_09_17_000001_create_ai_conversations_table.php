<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for AI conversations table.
 * 
 * Stores conversation threads between users and AI.
 * Each conversation can have multiple messages and context.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('title')->nullable();
            $table->string('context_type', 50)->default('general'); // general, server, admin, support, code
            $table->unsignedInteger('context_id')->nullable(); // related server/node ID if applicable
            $table->string('model_used', 100)->nullable();
            $table->enum('status', ['active', 'archived', 'deleted'])->default('active');
            $table->json('metadata')->nullable(); // additional context data
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['context_type', 'context_id']);
            $table->index('last_message_at');
            $table->index('created_at');

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};