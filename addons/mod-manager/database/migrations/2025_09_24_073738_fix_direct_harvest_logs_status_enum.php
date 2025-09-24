<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any invalid status values to valid ones
        DB::table('mod_direct_harvest_logs')
            ->where('status', 'stopping')
            ->update(['status' => 'stopped']);
            
        DB::table('mod_direct_harvest_logs')
            ->where('status', 'force_stopped')
            ->update(['status' => 'stopped']);
            
        DB::table('mod_direct_harvest_logs')
            ->where('status', 'processing_files')
            ->update(['status' => 'running']);

        // Now modify the enum to include the new values
        DB::statement("ALTER TABLE mod_direct_harvest_logs MODIFY COLUMN status ENUM('starting', 'running', 'completed', 'failed', 'stopped', 'stopping', 'force_stopped', 'processing_files') DEFAULT 'running'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any new status values to valid old ones
        DB::table('mod_direct_harvest_logs')
            ->whereIn('status', ['stopping', 'force_stopped', 'processing_files'])
            ->update(['status' => 'stopped']);

        // Revert to original enum
        DB::statement("ALTER TABLE mod_direct_harvest_logs MODIFY COLUMN status ENUM('starting', 'running', 'completed', 'failed', 'stopped') DEFAULT 'running'");
    }
};
