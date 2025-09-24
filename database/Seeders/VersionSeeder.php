<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VersionSeeder extends Seeder
{
    /**
     * Seed the application version setting
     */
    public function run(): void
    {
        // Initialize the app version setting if it doesn't exist
        DB::table('settings')->updateOrInsert(
            ['key' => 'app:version'],
            ['value' => '1.3.16']  // Default version for new installations
        );
        
        $this->command->info('Application version setting initialized');
    }
}