<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_code_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category', 100); // server_configuration, automation_script, etc.
            $table->text('description');
            $table->longText('template_code'); // The template code
            $table->json('parameters')->nullable(); // Template parameters/variables
            $table->string('language', 50)->default('bash'); // Programming language
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by');
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index('category');
            $table->index('language');
            $table->index('is_active');
            $table->index('usage_count');
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Add foreign key to code_generations table
        Schema::table('ai_code_generations', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')->on('ai_code_templates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('ai_code_generations', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });
        
        Schema::dropIfExists('ai_code_templates');
    }
};