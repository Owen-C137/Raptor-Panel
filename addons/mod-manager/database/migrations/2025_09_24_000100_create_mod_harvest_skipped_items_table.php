<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mod_harvest_skipped_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('harvest_log_id')->nullable();
            $table->string('session_id')->index();
            $table->enum('item_type', ['mod','file']);
            $table->unsignedBigInteger('curse_id')->nullable(); // mod id or file id
            $table->unsignedBigInteger('parent_curse_mod_id')->nullable(); // for files
            $table->string('reason_code', 50); // e.g. files_forbidden, mod_exception, cache_write_error
            $table->integer('http_status')->nullable();
            $table->string('endpoint', 255)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['item_type','reason_code']);
            $table->index('curse_id');
            $table->foreign('harvest_log_id')->references('id')->on('mod_direct_harvest_logs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mod_harvest_skipped_items');
    }
};
