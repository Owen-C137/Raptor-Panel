<?php

namespace Pterodactyl\Console\Commands\Update;

use Exception;
use Illuminate\Console\Command;
use Pterodactyl\Services\Updates\CustomUpdateService;
use Pterodactyl\Services\Updates\BackupService;

class ApplyCommand extends Command
{
    protected $signature = 'update:apply 
                            {--backup : Create a backup before applying the update}
                            {--no-backup : Skip creating a backup (not recommended)}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Apply available Raptor Panel updates';

    private CustomUpdateService $updateService;
    private BackupService $backupService;

    public function __construct(CustomUpdateService $updateService, BackupService $backupService)
    {
        parent::__construct();
        $this->updateService = $updateService;
        $this->backupService = $backupService;
    }

    public function handle(): int
    {
        try {
            $this->info('🚀 Raptor Panel Update System');
            $this->newLine();

            // Check if updates are available
            if (!$this->updateService->isUpdateAvailable()) {
                $this->info('✅ No updates available. Raptor Panel is already up to date!');
                return 0;
            }

            $currentVersion = $this->updateService->getCurrentVersion();
            $latestVersion = $this->updateService->getLatestVersion();
            $changedFiles = $this->updateService->getChangedFiles();

            // Show update information
            $this->info("📦 Current Version: {$currentVersion}");
            $this->info("🌟 Target Version:  {$latestVersion}");
            $this->info("📁 Files to Update: " . count($changedFiles));
            $this->newLine();

            // Confirmation prompt
            if (!$this->option('force')) {
                if (!$this->confirm('Do you want to proceed with the update?', true)) {
                    $this->warn('Update cancelled by user.');
                    return 0;
                }
                $this->newLine();
            }

            // Determine backup strategy
            $createBackup = true;
            if ($this->option('no-backup')) {
                $createBackup = false;
                $this->warn('⚠️  Backup disabled - this is not recommended!');
            } elseif ($this->option('backup') || (!$this->option('no-backup') && !$this->option('force'))) {
                $createBackup = $this->confirm('Create a backup before updating?', true);
            }

            $backupId = null;

            // Create backup if requested
            if ($createBackup) {
                $this->info('💾 Creating backup...');
                $backupName = 'pre-update-' . now()->format('Y-m-d-H-i-s');
                
                $progressBar = $this->output->createProgressBar();
                $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
                $progressBar->setMessage('Preparing backup...');
                $progressBar->start();

                try {
                    $backupId = $this->backupService->createBackup($backupName, function($current, $total, $message) use ($progressBar) {
                        $progressBar->setMaxSteps($total);
                        $progressBar->setProgress($current);
                        $progressBar->setMessage($message);
                    });
                    
                    $progressBar->setMessage('Backup completed');
                    $progressBar->finish();
                    $this->newLine();
                    $this->info("✅ Backup created: {$backupId}");
                    $this->newLine();

                } catch (Exception $e) {
                    $progressBar->finish();
                    $this->newLine();
                    $this->error("❌ Backup failed: {$e->getMessage()}");
                    
                    if (!$this->confirm('Continue without backup? (not recommended)', false)) {
                        return 1;
                    }
                    $this->newLine();
                }
            }

            // Apply the update
            $this->info('🔄 Applying update...');
            $updateProgressBar = $this->output->createProgressBar();
            $updateProgressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $updateProgressBar->setMessage('Preparing update...');
            $updateProgressBar->start();

            $result = $this->updateService->applyUpdate($backupId, function($current, $total, $message) use ($updateProgressBar) {
                $updateProgressBar->setMaxSteps($total);
                $updateProgressBar->setProgress($current);
                $updateProgressBar->setMessage($message);
            });

            $updateProgressBar->setMessage('Update completed');
            $updateProgressBar->finish();
            $this->newLine(2);

            // Success message
            $this->info('🎉 Update completed successfully!');
            $this->info("📦 Updated to version: {$result['new_version']}");
            $this->info("📁 Updated files: " . count($result['updated_files']));
            
            if ($backupId) {
                $this->newLine();
                $this->info("💾 Backup available: {$backupId}");
                $this->line("   Use 'php artisan update:rollback {$backupId}' to restore if needed");
            }

            $this->newLine();
            $this->line('💡 Don\'t forget to:');
            $this->line('   • Clear your browser cache');
            $this->line('   • Check that all features work as expected');
            $this->line('   • Review any new configuration options');

            return 0;

        } catch (Exception $e) {
            if (isset($updateProgressBar)) {
                $updateProgressBar->finish();
                $this->newLine();
            }

            $this->error('❌ Update failed:');
            $this->error("   {$e->getMessage()}");

            if ($backupId) {
                $this->newLine();
                $this->warn("💾 Backup is available: {$backupId}");
                $this->line("   Run 'php artisan update:rollback {$backupId}' to restore");
            }

            if ($this->option('verbose')) {
                $this->newLine();
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }
}