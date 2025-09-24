<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mod_mods', function (Blueprint $table) {
            // Add file sync tracking columns
            $table->timestamp('files_synced_at')->nullable()->after('last_sync_at');
            $table->enum('file_sync_status', ['pending', 'syncing', 'completed', 'failed'])->default('pending')->after('sync_status');
            
            // Add indexes for efficient querying
            $table->index(['files_synced_at', 'game_id'], 'idx_files_sync_game');
            $table->index('file_sync_status', 'idx_file_sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mod_mods', function (Blueprint $table) {
            $table->dropIndex('idx_files_sync_game');
            $table->dropIndex('idx_file_sync_status');
            $table->dropColumn(['files_synced_at', 'file_sync_status']);
        });
    }
};