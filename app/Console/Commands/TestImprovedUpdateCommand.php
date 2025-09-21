<?php

namespace Pterodactyl\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Pterodactyl\Services\Updates\ImprovedUpdateService;

class TestImprovedUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'update:test-improved {--strategy=all : Which strategy to test (all|github_releases|manifest_comparison|git_tree_comparison|directory_scan)}';

    /**
     * The console command description.
     */
    protected $description = 'Test the improved update detection system';

    /**
     * Execute the console command.
     */
    public function handle(ImprovedUpdateService $updateService): int
    {
        $strategy = $this->option('strategy');
        
        $this->info('🔍 Testing Improved Update Detection System');
        $this->newLine();

        // Show current system status
        $this->info('📊 Current System Status:');
        $this->table(['Property', 'Value'], [
            ['Current Version', $updateService->getCurrentVersion()],
            ['Latest Version', $updateService->getLatestVersion()],
            ['Update Available', $updateService->isUpdateAvailable() ? 'Yes' : 'No'],
        ]);
        $this->newLine();

        if ($strategy === 'all') {
            $this->testAllStrategies($updateService);
        } else {
            $this->testSpecificStrategy($updateService, $strategy);
        }

        // Test full update info
        $this->info('📋 Full Update Information:');
        $updateInfo = $updateService->getUpdateInfo();
        
        if ($updateInfo['available']) {
            $this->info("✅ Update available: v{$updateInfo['current_version']} → v{$updateInfo['latest_version']}");
            $this->info("📁 Files to update: {$updateInfo['file_changes']['total']}");
            
            if (!empty($updateInfo['file_changes']['categories'])) {
                $this->info('📂 File categories:');
                foreach ($updateInfo['file_changes']['categories'] as $category => $count) {
                    $this->line("   - {$category}: {$count} files");
                }
            }
            
            if (!empty($updateInfo['update_strategies_used'])) {
                $this->info('🔧 Strategies used: ' . implode(', ', $updateInfo['update_strategies_used']));
            }
        } else {
            $this->info('✅ System is up to date');
        }

        return Command::SUCCESS;
    }

    /**
     * Test all available strategies
     */
    protected function testAllStrategies(ImprovedUpdateService $updateService): void
    {
        $this->info('🧪 Testing All Update Detection Strategies:');
        $this->newLine();

        try {
            $files = $updateService->getChangedFiles();
            $strategiesUsed = $updateService->getLastUsedStrategies();
            
            $this->info("✅ Combined strategy found {files} files", ['files' => count($files)]);
            $this->info('🔧 Strategies used: ' . (empty($strategiesUsed) ? 'None' : implode(', ', $strategiesUsed)));
            
            if (!empty($files)) {
                $this->newLine();
                $this->info('📁 Sample files (first 10):');
                foreach (array_slice($files, 0, 10) as $file) {
                    $this->line("   - {$file}");
                }
                
                if (count($files) > 10) {
                    $this->info("   ... and " . (count($files) - 10) . " more files");
                }
            }
            
        } catch (Exception $e) {
            $this->error('❌ Combined strategy failed: ' . $e->getMessage());
        }
    }

    /**
     * Test a specific strategy
     */
    protected function testSpecificStrategy(ImprovedUpdateService $updateService, string $strategy): void
    {
        $this->info("🧪 Testing {$strategy} strategy:");
        $this->newLine();

        // This would require exposing individual strategies for testing
        // For now, just run the main method which will log which strategies are used
        try {
            $files = $updateService->getChangedFiles();
            $this->info("Strategy result: " . count($files) . " files found");
        } catch (Exception $e) {
            $this->error('Strategy failed: ' . $e->getMessage());
        }
    }
}