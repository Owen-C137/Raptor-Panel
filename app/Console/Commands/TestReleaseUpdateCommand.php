<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Pterodactyl\Services\Updates\GitHubReleaseUpdateService;

class TestReleaseUpdateCommand extends Command
{
    protected $signature = 'update:test-release {--check-only : Only check for updates without downloading}';
    protected $description = 'Test the new GitHub Release update system';

    public function handle(GitHubReleaseUpdateService $updateService): int
    {
        $this->info('🚀 Testing GitHub Release Update System');
        $this->newLine();

        try {
            // Check for updates
            $this->info('📡 Checking for updates...');
            $updateInfo = $updateService->checkForUpdates();

            $this->table(['Property', 'Value'], [
                ['Current Version', $updateInfo['current_version']],
                ['Latest Version', $updateInfo['latest_version']],
                ['Update Available', $updateInfo['available'] ? 'Yes' : 'No'],
                ['Release Name', $updateInfo['release_name'] ?? 'N/A'],
                ['Download Size', $updateInfo['download_size'] ?? 'N/A'],
            ]);

            if ($updateInfo['available'] && !$this->option('check-only')) {
                if ($this->confirm('🔥 Update available! Do you want to download and apply it?')) {
                    $this->info('⬇️ Downloading and applying update...');
                    
                    $result = $updateService->downloadAndApplyUpdate();
                    
                    if ($result['success']) {
                        $this->info('✅ ' . $result['message']);
                        $this->info('🎉 New version: ' . $result['new_version']);
                    } else {
                        $this->error('❌ ' . $result['message']);
                        return 1;
                    }
                }
            }

            if (!$updateInfo['available']) {
                $this->info('✅ You are already running the latest version!');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('💥 Test failed: ' . $e->getMessage());
            return 1;
        }
    }
}