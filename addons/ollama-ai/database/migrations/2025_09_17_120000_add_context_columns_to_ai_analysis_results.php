<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add context_type and context_id columns to ai_analysis_results table.
 * These are needed for compatibility with existing controller code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_analysis_results', function (Blueprint $table) {
            // Add the columns that the controllers are expecting
            $table->string('context_type', 50)->after('analysis_type')->nullable();
            $table->unsignedInteger('context_id')->after('context_type')->nullable();
            
            // Add index for the new columns
            $table->index(['context_type', 'context_id'], 'idx_context');
        });
    }

    public function down(): void
    {
        Schema::table('ai_analysis_results', function (Blueprint $table) {
            $table->dropIndex('idx_context');
            $table->dropColumn(['context_type', 'context_id']);
        });
    }
};