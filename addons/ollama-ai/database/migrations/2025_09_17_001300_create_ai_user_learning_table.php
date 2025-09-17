<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_user_learning', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('topic');
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('beginner');
            $table->json('progress_data')->nullable();
            $table->enum('learning_style', ['visual', 'auditory', 'kinesthetic', 'reading_writing', 'mixed'])->default('mixed');
            $table->integer('completion_percentage')->default(0);
            $table->integer('time_spent_seconds')->default(0);
            $table->timestamp('last_accessed')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'topic']);
            $table->index(['user_id', 'skill_level']);
            $table->index('completion_percentage');
            $table->index('last_accessed');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_user_learning');
    }
};