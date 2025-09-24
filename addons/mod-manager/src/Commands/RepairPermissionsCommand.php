<?php

namespace PterodactylAddons\ModManager\Commands;

use Illuminate\Console\Command;

class RepairPermissionsCommand extends Command
{
    protected $signature = 'mod-manager:repair-permissions {--precreate-second-level : Pre-create second level hex cache directories (65k dirs)}';
    protected $description = 'Diagnose and attempt repair of storage and cache directory structure for Mod Manager to prevent cache write errors.';

    public function handle(): int
    {
        $this->info('🔧 Mod Manager Permission & Cache Structure Repair');
        $storagePath = storage_path();
        $cacheBase = storage_path('framework/cache/data');
        $paths = [
            $storagePath,
            storage_path('framework'),
            storage_path('framework/cache'),
            $cacheBase,
        ];

        foreach ($paths as $p) {
            if (!is_dir($p)) {
                @mkdir($p, 0775, true);
                $this->line("📁 Created missing directory: {$p}");
            }
            if (!is_writable($p)) {
                $this->warn("⚠️  Directory not writable: {$p}");
            } else {
                $this->line("✅ Writable: {$p}");
            }
        }

        // First-level (00..ff) directories
        $this->line('🧩 Ensuring first-level hex directories exist (00..ff)...');
        for ($i = 0; $i < 256; $i++) {
            $d = $cacheBase . '/' . sprintf('%02x', $i);
            if (!is_dir($d)) {
                @mkdir($d, 0775, true);
            }
        }
        $this->info('✅ First-level hex directories ensured.');

        if ($this->option('precreate-second-level')) {
            $this->warn('⏱️ Pre-creating second-level directories (this may take a while)...');
            $count = 0;
            for ($i = 0; $i < 256; $i++) {
                $first = sprintf('%02x', $i);
                for ($j = 0; $j < 256; $j++) {
                    $second = sprintf('%02x', $j);
                    $dir = $cacheBase . '/' . $first . '/' . $second;
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0775, true);
                    }
                    $count++;
                }
            }
            $this->info("✅ Created/verified {$count} second-level directories.");
        }

        // Write test
        $testFile = $cacheBase . '/permission_test_' . uniqid() . '.txt';
        $result = @file_put_contents($testFile, 'ok');
        if ($result === false) {
            $this->error('❌ Failed to write test file. Check ownership (chown -R www-data:www-data storage).');
        } else {
            $this->info('✍️  Test file write succeeded.');
            @unlink($testFile);
        }

        $this->line('ℹ️  For best stability consider using Redis cache: set CACHE_DRIVER=redis in .env');
        $this->info('Done.');
        return self::SUCCESS;
    }
}
