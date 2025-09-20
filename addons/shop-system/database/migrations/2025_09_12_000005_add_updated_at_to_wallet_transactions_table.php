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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Add updated_at column after created_at if it doesn't exist
            if (!Schema::hasColumn('wallet_transactions', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Drop updated_at column if it exists
            if (Schema::hasColumn('wallet_transactions', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
