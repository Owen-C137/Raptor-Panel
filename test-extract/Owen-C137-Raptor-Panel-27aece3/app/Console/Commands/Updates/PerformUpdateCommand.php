<?php

namespace Pterodactyl\Console\Commands\Updates;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Services\Updates\UpdateOrchestrationService;
use Pterodactyl\Services\Updates\Database\SessionService;
use Pterodactyl\Services\Updates\ValidationService;
use Pterodactyl\Services\Updates\ProgressTrackingService;

/**
 * PerformUpdateCommand provides CLI interface for executing system updates.
 * 
 * This command allows administrators to perform updates from the command line,
 * useful for automated deployments and scripted updates.
 */
class PerformUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updates:start 
                          {version : The version to update to}
                          {--no-backup : Skip backup creation}
                          {--no-rollback : Disable automatic rollback on failure}
                          {--skip-validation : Skip pre-update validation}
                          {--maintenance : Enable maintenance mode during update}
                          {--force : Force update even if validation fails}
                          {--quiet : Suppress output except errors}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a system update to the specified version';

    private UpdateOrchestrationService $orchestrationService;
    private SessionService $sessionService;
    private ValidationService $validationService;
    private ProgressTrackingService $progressService;

    public function __construct(
        UpdateOrchestrationService $orchestrationService,
        SessionService $sessionService,
        ValidationService $validationService,
        ProgressTrackingService $progressService
    ) {
        parent::__construct();
        $this->orchestrationService = $orchestrationService;
        $this->sessionService = $sessionService;
        $this->validationService = $validationService;
        $this->progressService = $progressService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $version = $this->argument('version');
        $quiet = $this->option('quiet');

        try {
            if (!$quiet) {
                $this->info("Starting update to version {$version}...");
                $this->line('');
            }

            // Pre-update validation
            if (!$this->option('skip-validation')) {
                if (!$this->performPreUpdateValidation($quiet)) {
                    return 1; // Validation failed
                }
            }

            // Create update session
            $sessionResult = $this->sessionService->createSession([
                'target_version' => $version,
                'initiated_by' => 'cli',
                'cli_options' => $this->options(),
            ]);

            if (!$sessionResult['success']) {
                $this->error('Failed to create update session: ' . $sessionResult['error']);
                return 2;
            }

            $sessionId = $sessionResult['session']['id'];

            if (!$quiet) {
                $this->info("Update session created: {$sessionId}");
            }

            // Setup progress monitoring for CLI
            $this->setupProgressMonitoring($sessionId, $quiet);

            // Execute update
            $updateOptions = [
                'target_version' => $version,
                'create_backup' => !$this->option('no-backup'),
                'auto_rollback' => !$this->option('no-rollback'),
                'maintenance_mode' => $this->option('maintenance'),
            ];

            $result = $this->orchestrationService->executeUpdate($sessionId, $updateOptions);

            if ($result['success']) {
                if (!$quiet) {
                    $this->line('');
                    $this->info('✅ Update completed successfully!');
                }
                return 0; // Success
            } else {
                $this->error('❌ Update failed: ' . $result['error']);
                return 3; // Update failed
            }

        } catch (Exception $e) {
            $this->error('💥 Update process crashed: ' . $e->getMessage());
            Log::error('Update command failed', [
                'version' => $version,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 4; // Exception occurred
        }
    }

    /**
     * Perform pre-update validation.
     */
    private function performPreUpdateValidation(bool $quiet): bool
    {
        if (!$quiet) {
            $this->info('Performing pre-update validation...');
        }

        $validation = $this->validationService->validatePreUpdate();

        if (!$validation['valid']) {
            $this->error('❌ Pre-update validation failed:');
            
            foreach ($validation['errors'] as $error) {
                $this->error("  • {$error}");
            }

            if (!empty($validation['warnings'])) {
                $this->warn('⚠️  Warnings:');
                foreach ($validation['warnings'] as $warning) {
                    $this->warn("  • {$warning}");
                }
            }

            if (!$this->option('force')) {
                $this->error('Use --force to proceed despite validation errors.');
                return false;
            } else {
                $this->warn('⚠️  Proceeding with validation errors due to --force flag');
            }
        } else {
            if (!$quiet) {
                $this->info('✅ Pre-update validation passed');
            }

            if (!empty($validation['warnings']) && !$quiet) {
                $this->warn('⚠️  Validation warnings:');
                foreach ($validation['warnings'] as $warning) {
                    $this->warn("  • {$warning}");
                }
            }
        }

        return true;
    }

    /**
     * Setup progress monitoring for CLI output.
     */
    private function setupProgressMonitoring(string $sessionId, bool $quiet): void
    {
        if ($quiet) {
            return;
        }

        $this->progressService->addProgressListener(function($sessionId, $progressData) {
            $percentage = $progressData['progress_percentage'] ?? 0;
            $step = $progressData['current_step'] ?? 'Processing...';

            // Create progress bar
            $bar = str_repeat('█', (int)($percentage / 5));
            $empty = str_repeat('░', 20 - (int)($percentage / 5));
            
            $this->line(sprintf(
                "\r[%s%s] %d%% - %s",
                $bar,
                $empty,
                $percentage,
                $step
            ), null, false);

            if ($percentage >= 100) {
                $this->line(''); // New line after completion
            }
        });
    }
}