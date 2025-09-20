<?php

namespace Pterodactyl\Console\Commands\Update;

use Exception;
use Illuminate\Console\Command;
use Pterodactyl\Services\Updates\BackupService;

class RollbackCommand extends Command
{
    protected $signature = 'update:rollback 
                            {backup_id? : The backup ID to restore from}
                            {--list : List available backups}
                            {--latest : Restore from the latest backup}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Rollback Raptor Panel to a previous version using a backup';

    private BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    public function handle(): int
    {
        try {
            if ($this->option('list')) {
                return $this->listBackups();
            }

            $this->info('🔄 Raptor Panel Rollback System');
            $this->newLine();

            $backupId = $this->getBackupId();
            
            if (!$backupId) {
                $this->error('❌ No backup specified or available.');
                $this->line('   Use --list to see available backups');
                return 1;
            }

            // Validate backup exists
            $backups = $this->backupService->listBackups();
            $backup = collect($backups)->firstWhere('id', $backupId);
            
            if (!$backup) {
                $this->error("❌ Backup '{$backupId}' not found.");
                $this->line('   Use --list to see available backups');
                return 1;
            }

            // Show backup information
            $this->info("📦 Backup ID:   {$backup['id']}");
            $this->info("📅 Created:     {$backup['created_at']}");
            $this->info("📁 Size:        {$backup['size_human']}");
            $this->info("📝 Description: {$backup['description']}");
            $this->newLine();

            $this->warn('⚠️  This will overwrite your current Raptor Panel installation!');
            $this->newLine();

            // Confirmation
            if (!$this->option('force')) {
                if (!$this->confirm("Are you sure you want to rollback to backup '{$backupId}'?", false)) {
                    $this->warn('Rollback cancelled by user.');
                    return 0;
                }
                $this->newLine();
            }

            // Perform rollback
            $this->info('🔄 Performing rollback...');
            $progressBar = $this->output->createProgressBar();
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $progressBar->setMessage('Preparing rollback...');
            $progressBar->start();

            $this->backupService->restoreBackup($backupId, function($current, $total, $message) use ($progressBar) {
                $progressBar->setMaxSteps($total);
                $progressBar->setProgress($current);
                $progressBar->setMessage($message);
            });

            $progressBar->setMessage('Rollback completed');
            $progressBar->finish();
            $this->newLine(2);

            $this->info('✅ Rollback completed successfully!');
            $this->info("📦 Restored from backup: {$backupId}");
            $this->newLine();
            
            $this->line('💡 Remember to:');
            $this->line('   • Clear your browser cache');
            $this->line('   • Verify all functionality works correctly');
            $this->line('   • Check file permissions if needed');

            return 0;

        } catch (Exception $e) {
            if (isset($progressBar)) {
                $progressBar->finish();
                $this->newLine();
            }

            $this->error('❌ Rollback failed:');
            $this->error("   {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->newLine();
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }

    private function getBackupId(): ?string
    {
        // If backup ID provided as argument
        if ($this->argument('backup_id')) {
            return $this->argument('backup_id');
        }

        // If --latest option used
        if ($this->option('latest')) {
            $backups = $this->backupService->listBackups();
            if (empty($backups)) {
                return null;
            }
            return $backups[0]['id']; // First backup is the latest
        }

        // Interactive selection
        $backups = $this->backupService->listBackups();
        if (empty($backups)) {
            return null;
        }

        $this->info('Available backups:');
        $choices = [];
        foreach ($backups as $index => $backup) {
            $label = "{$backup['id']} - {$backup['created_at']} ({$backup['size_human']})";
            $choices[] = $label;
            $this->line("  " . ($index + 1) . ". {$label}");
        }
        $this->newLine();

        $selection = $this->choice('Select a backup to restore from', $choices);
        $selectedIndex = array_search($selection, $choices);
        
        return $backups[$selectedIndex]['id'] ?? null;
    }

    private function listBackups(): int
    {
        $this->info('📦 Available Backups');
        $this->newLine();

        try {
            $backups = $this->backupService->listBackups();
            
            if (empty($backups)) {
                $this->warn('No backups available.');
                $this->line('   Backups will be created automatically when you apply updates.');
                return 0;
            }

            $headers = ['ID', 'Created', 'Size', 'Description'];
            $rows = [];

            foreach ($backups as $backup) {
                $rows[] = [
                    $backup['id'],
                    $backup['created_at'],
                    $backup['size_human'],
                    $backup['description'],
                ];
            }

            $this->table($headers, $rows);
            $this->newLine();
            $this->info("Total backups: " . count($backups));
            $this->line('Use: php artisan update:rollback <backup_id>');

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Failed to list backups:');
            $this->error("   {$e->getMessage()}");
            return 1;
        }
    }
}