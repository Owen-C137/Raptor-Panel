<?php

namespace PterodactylAddons\ModManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ModManagerPublishAssetsCommand extends Command
{
    protected $signature = 'mod-manager:publish-assets {--force : Force overwrite existing assets}';
    protected $description = 'Publish mod-manager assets to public directory';

    public function handle(): int
    {
        $this->info('🎨 Publishing Mod Manager Assets');
        $this->info('=================================');

        try {
            $this->publishAssets();
            
            $this->info('');
            $this->info('✅ Assets published successfully!');
            $this->info('📁 Location: public/assets/mod-manager/');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Asset publishing failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function publishAssets(): void
    {
        $sourceDir = base_path('addons/mod-manager/resources/images');
        $targetDir = public_path('assets/mod-manager');

        if (!File::exists($sourceDir)) {
            throw new \Exception('Source assets directory not found: ' . $sourceDir);
        }

        // Create target directory if it doesn't exist
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
            $this->info('📁 Created assets directory: ' . $targetDir);
        }

        // Get all files from source
        $files = File::files($sourceDir);
        $publishedCount = 0;

        if (empty($files)) {
            $this->warn('⚠️  No image files found in: ' . $sourceDir);
            return;
        }

        $this->info('📤 Publishing ' . count($files) . ' asset files...');

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $targetPath = $targetDir . '/' . $filename;
            
            // Check if file already exists
            if (File::exists($targetPath) && !$this->option('force')) {
                $this->line("   ⏭️  Skipped (exists): {$filename}");
                continue;
            }

            // Copy file
            File::copy($file->getPathname(), $targetPath);
            $publishedCount++;
            
            $this->line("   ✅ Published: {$filename}");
        }

        // Set proper permissions
        if ($publishedCount > 0) {
            exec('chown -R www-data:www-data ' . escapeshellarg($targetDir));
            exec('chmod -R 644 ' . escapeshellarg($targetDir . '/*'));
            exec('chmod 755 ' . escapeshellarg($targetDir));
            
            $this->info("🔧 Set proper permissions for {$publishedCount} files");
        } else {
            $this->info('📝 No new files to publish');
        }
    }
}