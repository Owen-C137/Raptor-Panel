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
        Schema::create('mod_installations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('server_id');
            $table->unsignedBigInteger('mod_id');
            $table->unsignedBigInteger('file_id');
            
            // Installation details
            $table->string('installation_path', 500);
            $table->enum('status', ['pending', 'installing', 'installed', 'failed', 'removed'])->default('pending');
            
            // Version management
            $table->string('installed_version', 100)->nullable();
            $table->string('target_version', 100)->nullable();
            
            // Settings
            $table->boolean('is_enabled')->default(true);
            $table->boolean('auto_update')->default(false);
            
            // Installation tracking
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamp('last_check_at')->nullable();
            
            // Error handling
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->foreign('mod_id')->references('id')->on('mod_mods')->onDelete('cascade');
            $table->foreign('file_id')->references('id')->on('mod_files')->onDelete('cascade');
            
            // Indexes
            $table->index(['user_id', 'server_id']);
            $table->index('server_id');
            $table->index('mod_id');
            $table->index('status');
            $table->index('installed_at');
            
            // Unique constraint
            $table->unique(['server_id', 'mod_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_installations');
    }
};