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
        Schema::create('mod_compatibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mod_a_id')->constrained('mod_mods')->onDelete('cascade')->comment('First mod in comparison');
            $table->foreignId('mod_b_id')->constrained('mod_mods')->onDelete('cascade')->comment('Second mod in comparison');
            
            // Compatibility status
            $table->enum('compatibility_status', ['compatible', 'incompatible', 'unknown', 'requires_patch'])->default('unknown')->comment('Compatibility status');
            
            // Version specific compatibility
            $table->string('version_a', 100)->nullable()->comment('Version of mod A');
            $table->string('version_b', 100)->nullable()->comment('Version of mod B');
            $table->string('game_version', 100)->nullable()->comment('Game version tested');
            $table->string('mod_loader', 50)->nullable()->comment('Mod loader used');
            
            // Details
            $table->text('notes')->nullable()->comment('Compatibility notes');
            $table->unsignedInteger('reported_by')->nullable()->comment('User who reported this');
            $table->boolean('verified')->default(false)->comment('Admin verified compatibility');
            $table->unsignedInteger('verified_by')->nullable()->comment('Admin who verified');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['mod_a_id', 'mod_b_id']);
            $table->index('compatibility_status');
            $table->index(['mod_a_id', 'compatibility_status']);
            $table->index(['mod_b_id', 'compatibility_status']);
            
            // Unique constraint for specific version combinations
            $table->unique(['mod_a_id', 'mod_b_id', 'version_a', 'version_b', 'game_version'], 'unique_mod_pair_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_compatibility');
    }
};