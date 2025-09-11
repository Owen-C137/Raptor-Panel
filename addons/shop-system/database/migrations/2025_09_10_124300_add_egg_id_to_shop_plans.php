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
        Schema::table('shop_plans', function (Blueprint $table) {
            // Add egg_id column that the model expects
            $table->unsignedInteger('egg_id')->nullable()->after('category_id');
            
            // Add foreign key constraint
            $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_plans', function (Blueprint $table) {
            $table->dropForeign(['egg_id']);
            $table->dropColumn('egg_id');
        });
    }
};
