<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates all tables for the advanced auto-update system.
     * This includes version tracking, session management, backups,
     * file changes, migrations, and settings.
     */
    public function up(): void
    {
        // 1. Panel Versions - Track all available versions
        Schema::create('panel_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->unique();
            $table->boolean('is_current')->default(false);
            $table->timestamp('release_date')->nullable();
            $table->text('release_notes')->nullable();
            $table->json('changelog_data')->nullable();
            $table->unsignedBigInteger('github_release_id')->nullable();
            $table->string('github_tag', 50)->nullable();
            $table->string('release_url', 500)->nullable();
            $table->string('download_url', 500)->nullable();
            $table->string('archive_checksum', 64)->nullable();
            $table->boolean('requires_migration')->default(false);
            $table->json('migration_files')->nullable(); // Array of migration files needed
            $table->timestamps();
            
            // Indexes
            $table->index('version');
            $table->index('is_current');
            $table->index('release_date');
        });

        // 2. Update Sessions - Track individual update processes
        Schema::create('update_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->unique();
            $table->string('from_version', 20);
            $table->string('to_version', 20);
            $table->enum('status', [
                'pending', 'downloading', 'backing_up', 'extracting', 
                'updating_files', 'running_migrations', 'finalizing', 
                'completed', 'failed', 'rolled_back'
            ])->default('pending');
            $table->tinyInteger('progress_percentage')->unsigned()->default(0);
            $table->string('current_step', 100)->nullable();
            $table->integer('total_steps')->default(0);
            $table->integer('completed_steps')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('error_trace')->nullable();
            $table->uuid('backup_id')->nullable();
            $table->json('files_to_update')->nullable(); // List of files that need updating
            $table->json('files_updated')->nullable(); // List of files successfully updated
            $table->json('files_failed')->nullable(); // List of files that failed to update
            $table->json('migrations_to_run')->nullable(); // List of migrations to run
            $table->json('migrations_completed')->nullable(); // List of completed migrations
            $table->json('rollback_data')->nullable(); // Data needed for rollback
            $table->unsignedInteger('initiated_by')->nullable(); // User ID who started update
            $table->timestamps();
            
            // Foreign keys and indexes
            $table->foreign('initiated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('session_id');
            $table->index('status');
            $table->index('started_at');
        });

        // 3. Update Backups - Track all backup files
        Schema::create('update_backups', function (Blueprint $table) {
            $table->id();
            $table->uuid('backup_id')->unique();
            $table->uuid('session_id');
            $table->string('version', 20);
            $table->string('backup_path', 500);
            $table->unsignedBigInteger('backup_size');
            $table->unsignedBigInteger('compressed_size')->nullable();
            $table->string('checksum', 64);
            $table->text('description')->nullable();
            $table->boolean('includes_database')->default(true);
            $table->string('database_dump_path', 500)->nullable();
            $table->json('files_backed_up')->nullable(); // List of backed up files
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('expires_at')->nullable(); // Auto-cleanup date
            $table->timestamps();
            
            // Foreign keys and indexes
            $table->foreign('session_id')->references('session_id')->on('update_sessions')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('backup_id');
            $table->index('version');
            $table->index('expires_at');
        });

        // 4. Update File Changes - Track individual file updates
        Schema::create('update_file_changes', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->string('file_path', 500);
            $table->enum('change_type', ['added', 'modified', 'deleted']);
            $table->string('old_checksum', 64)->nullable();
            $table->string('new_checksum', 64)->nullable();
            $table->unsignedBigInteger('old_size')->nullable();
            $table->unsignedBigInteger('new_size')->nullable();
            $table->string('backup_path', 500)->nullable(); // Where the original file was backed up
            $table->enum('status', ['pending', 'completed', 'failed', 'skipped'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Foreign keys and indexes
            $table->foreign('session_id')->references('session_id')->on('update_sessions')->onDelete('cascade');
            $table->index('session_id');
            $table->index('file_path');
            $table->index('change_type');
            $table->index('status');
        });

        // 5. Update Migrations - Track database migration updates
        Schema::create('update_migrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->string('migration_file', 255);
            $table->integer('batch_number');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'rolled_back'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('rollback_sql')->nullable(); // SQL needed to rollback this migration
            $table->timestamps();
            
            // Foreign keys and indexes
            $table->foreign('session_id')->references('session_id')->on('update_sessions')->onDelete('cascade');
            $table->index('session_id');
            $table->index('migration_file');
            $table->index('status');
        });

        // 6. Update Settings - Configurable system settings
        Schema::create('update_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->json('setting_value');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // System settings can't be modified via UI
            $table->timestamps();
            
            // Indexes
            $table->index('setting_key');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops all update system tables in reverse order
     * to handle foreign key constraints properly.
     */
    public function down(): void
    {
        // Drop tables in reverse order to handle foreign keys
        Schema::dropIfExists('update_settings');
        Schema::dropIfExists('update_migrations');
        Schema::dropIfExists('update_file_changes');
        Schema::dropIfExists('update_backups');
        Schema::dropIfExists('update_sessions');
        Schema::dropIfExists('panel_versions');
    }
};
