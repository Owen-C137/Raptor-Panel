<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_help_contexts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('route_name');
            $table->json('context_data')->nullable();
            $table->json('help_data')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
            
            $table->index(['user_id', 'route_name']);
            $table->index('generated_at');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_help_contexts');
    }
};