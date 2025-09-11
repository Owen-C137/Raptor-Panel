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
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json, etc.
            $table->text('description')->nullable();
            $table->string('group')->default('general'); // general, payment, email, etc.
            $table->boolean('is_public')->default(false); // Can be accessed by frontend
            $table->timestamps();

            $table->index(['group', 'key']);
            $table->index('is_public');
        });

        // Insert default settings
        DB::table('shop_settings')->insert([
            [
                'key' => 'shop_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable/disable the shop system',
                'group' => 'general',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'shop_name',
                'value' => 'Pterodactyl Shop',
                'type' => 'string',
                'description' => 'Name of the shop',
                'group' => 'general',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'shop_currency',
                'value' => 'USD',
                'type' => 'string',
                'description' => 'Shop currency code',
                'group' => 'general',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'paypal_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable PayPal payments',
                'group' => 'payment',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'stripe_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable Stripe payments',
                'group' => 'payment',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'wallet_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable wallet system',
                'group' => 'payment',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
