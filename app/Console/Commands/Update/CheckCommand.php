<?php

namespace Pterodactyl\Console\Commands\Update;

use Exception;
use Illuminate\Console\Command;
use Pterodactyl\Services\Updates\CustomUpdateService;
use Pterodactyl\Services\Updates\ChangelogService;

class CheckCommand extends Command
{
    protected $signature = 'update:check 
                            {--json : Output results in JSON format}
                            {--force : Force check, ignoring cache}';

    protected $description = 'Check for available Raptor Panel updates';

    private CustomUpdateService $updateService;
    private ChangelogService $changelogService;

    public function __construct(CustomUpdateService $updateService, ChangelogService $changelogService)
    {
        parent::__construct();
        $this->updateService = $updateService;
        $this->changelogService = $changelogService;
    }

    public function handle(): int
    {
        try {
            $this->info('🔍 Checking for Raptor Panel updates...');
            $this->newLine();

            $currentVersion = $this->updateService->getCurrentVersion();
            $latestVersion = $this->updateService->getLatestVersion();
            $updateAvailable = $this->updateService->isUpdateAvailable();

            if ($this->option('json')) {
                $result = [
                    'current_version' => $currentVersion,
                    'latest_version' => $latestVersion,
                    'update_available' => $updateAvailable,
                    'checked_at' => now()->toISOString(),
                ];

                if ($updateAvailable) {
                    $changelog = $this->changelogService->getFormattedChangelog($latestVersion);
                    $result['changelog'] = $changelog;
                    $result['changed_files'] = count($this->updateService->getChangedFiles());
                }

                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return 0;
            }

            // Display current version info
            $this->info("📦 Current Version: {$currentVersion}");
            $this->info("🌟 Latest Version:  {$latestVersion}");
            $this->newLine();

            if (!$updateAvailable) {
                $this->info('✅ Raptor Panel is up to date!');
                $this->line('   No updates are available at this time.');
                return 0;
            }

            // Update is available
            $this->warn('🔔 Update Available!');
            $this->line("   A new version ({$latestVersion}) is available for download.");
            $this->newLine();

            // Get changelog information
            $changelog = $this->changelogService->getFormattedChangelog($latestVersion);
            
            if (!empty($changelog['features'])) {
                $this->info('🆕 New Features:');
                foreach ($changelog['features'] as $feature) {
                    $this->line("   • {$feature}");
                }
                $this->newLine();
            }

            if (!empty($changelog['fixes'])) {
                $this->info('🐛 Bug Fixes:');
                foreach ($changelog['fixes'] as $fix) {
                    $this->line("   • {$fix}");
                }
                $this->newLine();
            }

            if (!empty($changelog['changes'])) {
                $this->info('📝 Changes:');
                foreach ($changelog['changes'] as $change) {
                    $this->line("   • {$change}");
                }
                $this->newLine();
            }

            // Show file count
            $changedFiles = $this->updateService->getChangedFiles();
            $fileCount = count($changedFiles);
            $this->info("📁 Files to Update: {$fileCount}");
            
            if ($this->option('verbose')) {
                $this->line('   Changed files:');
                foreach (array_slice($changedFiles, 0, 10) as $file) {
                    $this->line("   - {$file}");
                }
                if ($fileCount > 10) {
                    $remaining = $fileCount - 10;
                    $this->line("   ... and {$remaining} more files");
                }
            }

            $this->newLine();
            $this->line('💡 Run <comment>php artisan update:apply</comment> to install the update.');
            $this->line('💡 Run <comment>php artisan update:apply --backup</comment> to create a backup first.');

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Failed to check for updates:');
            $this->error("   {$e->getMessage()}");
            
            if ($this->option('verbose')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }
}