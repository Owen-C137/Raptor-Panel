<?php

namespace Pterodactyl\Console\Commands\Updates;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Services\Updates\GitHub\GitHubReleaseService;
use Pterodactyl\Services\Updates\Database\VersionService;

/**
 * CheckUpdatesCommand provides CLI interface for checking available updates.
 * 
 * This command allows administrators to check for updates from the command line,
 * useful for scripting and automation purposes.
 */
class CheckUpdatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updates:check 
                          {--format=table : Output format (table, json, plain)}
                          {--include-prereleases : Include pre-release versions}
                          {--quiet : Only show available updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for available updates to Pterodactyl panel';

    private GitHubReleaseService $githubReleaseService;
    private VersionService $versionService;

    public function __construct(
        GitHubReleaseService $githubReleaseService,
        VersionService $versionService
    ) {
        parent::__construct();
        $this->githubReleaseService = $githubReleaseService;
        $this->versionService = $versionService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $this->info('Checking for updates...');

            // Get current version
            $currentVersion = $this->versionService->getCurrentVersion();
            
            // Check for updates
            $updateCheck = $this->githubReleaseService->checkForUpdates([
                'include_prereleases' => $this->option('include-prereleases'),
            ]);

            $format = $this->option('format');
            $quiet = $this->option('quiet');

            if (!$quiet) {
                $this->displayCurrentVersion($currentVersion);
            }

            if ($updateCheck['updates_available']) {
                $this->displayUpdateAvailable($updateCheck, $format, $quiet);
                return 0; // Updates available
            } else {
                if (!$quiet) {
                    $this->displayUpToDate($format);
                }
                return 1; // No updates available
            }

        } catch (Exception $e) {
            $this->error('Failed to check for updates: ' . $e->getMessage());
            Log::error('Update check command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 2; // Error
        }
    }

    /**
     * Display current version information.
     */
    private function displayCurrentVersion(string $currentVersion): void
    {
        $this->line('Current Version: <info>' . $currentVersion . '</info>');
        $this->line('');
    }

    /**
     * Display update available information.
     */
    private function displayUpdateAvailable(array $updateCheck, string $format, bool $quiet): void
    {
        $release = $updateCheck['latest_release'];

        if ($quiet) {
            $this->line($release['tag_name']);
            return;
        }

        switch ($format) {
            case 'json':
                $this->line(json_encode([
                    'updates_available' => true,
                    'current_version' => $this->versionService->getCurrentVersion(),
                    'latest_version' => $release['tag_name'],
                    'release_date' => $release['published_at'],
                    'release_url' => $release['html_url'],
                    'download_url' => $release['zipball_url'],
                ], JSON_PRETTY_PRINT));
                break;

            case 'plain':
                $this->line('Update Available: ' . $release['tag_name']);
                $this->line('Released: ' . $release['published_at']);
                $this->line('URL: ' . $release['html_url']);
                break;

            case 'table':
            default:
                $this->info('🎉 Update Available!');
                $this->line('');

                $this->table(['Property', 'Value'], [
                    ['New Version', $release['tag_name']],
                    ['Current Version', $this->versionService->getCurrentVersion()],
                    ['Release Date', date('Y-m-d H:i:s', strtotime($release['published_at']))],
                    ['Release Notes', $release['html_url']],
                    ['Pre-release', $release['prerelease'] ? 'Yes' : 'No'],
                ]);

                if (!empty($release['body'])) {
                    $this->line('');
                    $this->line('<comment>Release Notes:</comment>');
                    $this->line(substr($release['body'], 0, 500) . (strlen($release['body']) > 500 ? '...' : ''));
                }

                $this->line('');
                $this->line('To update, run: <comment>php artisan updates:start ' . $release['tag_name'] . '</comment>');
                break;
        }
    }

    /**
     * Display up-to-date information.
     */
    private function displayUpToDate(string $format): void
    {
        switch ($format) {
            case 'json':
                $this->line(json_encode([
                    'updates_available' => false,
                    'current_version' => $this->versionService->getCurrentVersion(),
                    'message' => 'System is up to date',
                ], JSON_PRETTY_PRINT));
                break;

            case 'plain':
                $this->line('System is up to date');
                break;

            case 'table':
            default:
                $this->info('✅ System is up to date!');
                $this->line('You are running the latest version of Pterodactyl.');
                break;
        }
    }
}