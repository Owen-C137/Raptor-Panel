<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_code_generations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('type', 100); // server_configuration, automation_script, etc.
            $table->json('parameters'); // Generation parameters
            $table->longText('generated_code'); // The actual generated code
            $table->text('documentation')->nullable(); // Code documentation
            $table->json('validation_results')->nullable(); // Validation results
            $table->json('context_data')->nullable(); // Context information
            $table->decimal('ai_confidence', 3, 2)->default(0.80); // AI confidence score
            $table->unsignedBigInteger('template_id')->nullable(); // Reference to template used
            $table->boolean('is_successful')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index('created_at');
            $table->index('ai_confidence');
            $table->index('is_successful');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_code_generations');
    }
};