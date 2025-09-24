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
        Schema::create('mod_files', function (Blueprint $table) {
            $table->id();
            $table->integer('curse_file_id')->unique()->comment('CurseForge file ID');
            $table->foreignId('mod_id')->constrained('mod_mods')->onDelete('cascade')->comment('Associated mod');
            $table->string('display_name')->comment('Display name of the file');
            $table->string('file_name')->comment('Actual file name');
            
            // File details
            $table->tinyInteger('release_type')->comment('1=Release, 2=Beta, 3=Alpha');
            $table->tinyInteger('file_status')->comment('File status code');
            $table->boolean('is_available')->default(true)->comment('File availability');
            
            // Download info
            $table->string('download_url', 500)->nullable()->comment('Download URL');
            $table->bigInteger('file_length')->default(0)->comment('File size in bytes');
            $table->bigInteger('download_count')->default(0)->comment('Download count');
            $table->bigInteger('file_size_on_disk')->default(0)->comment('Actual file size on disk');
            
            // Compatibility
            $table->json('game_versions')->nullable()->comment('Compatible game versions');
            $table->json('sortable_game_versions')->nullable()->comment('Structured version data');
            $table->json('mod_loader_types')->nullable()->comment('Mod loaders (Forge, Fabric, etc.)');
            $table->integer('game_version_type_id')->nullable()->comment('Game version type ID');
            
            // Dependencies
            $table->json('dependencies')->nullable()->comment('Mod dependencies');
            
            // Security
            $table->json('hashes')->nullable()->comment('File integrity hashes');
            $table->bigInteger('file_fingerprint')->nullable()->comment('File fingerprint');
            $table->json('modules')->nullable()->comment('Mod modules information');
            
            // Dates
            $table->timestamp('file_date')->nullable()->comment('File date');
            $table->timestamp('upload_date')->nullable()->comment('Upload date');
            
            // Server pack info
            $table->boolean('is_server_pack')->default(false)->comment('Is server pack file');
            $table->integer('server_pack_file_id')->nullable()->comment('Associated server pack file ID');
            
            // Early access
            $table->boolean('is_early_access_content')->default(false)->comment('Early access content');
            $table->timestamp('early_access_end_date')->nullable()->comment('Early access end date');
            
            // Alternative files
            $table->boolean('expose_as_alternative')->default(false)->comment('Expose as alternative');
            $table->integer('parent_project_file_id')->nullable()->comment('Parent project file ID');
            $table->integer('alternate_file_id')->nullable()->comment('Alternative file ID');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('curse_file_id');
            $table->index('mod_id');
            $table->index('file_name');
            $table->index('release_type');
            $table->index('file_date', 'idx_file_date_desc'); // Descending order
            $table->index('download_count', 'idx_download_count_desc'); // Descending order
            $table->index('is_available');
            $table->index(['mod_id', 'release_type']);
            $table->index(['mod_id', 'file_date']);
            $table->index(['mod_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_files');
    }
};