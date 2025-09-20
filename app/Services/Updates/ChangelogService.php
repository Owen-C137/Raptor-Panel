<?php

namespace Pterodactyl\Services\Updates;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ChangelogService
{
    public function __construct(
        protected Client $client,
        protected GitHubFileService $githubFileService
    ) {}

    /**
     * Get changelog content for a specific version.
     */
    public function getChangelogForVersion(string $version): array
    {
        $changelogContent = $this->getChangelogContent();
        
        if (!$changelogContent) {
            return $this->getDefaultChangelog($version);
        }

        return $this->parseChangelog($changelogContent, $version);
    }

    /**
     * Get the full changelog content from GitHub.
     */
    public function getChangelogContent(): ?string
    {
        return $this->githubFileService->getFileContent('CHANGELOG.md');
    }

    /**
     * Parse markdown changelog content for a specific version.
     */
    protected function parseChangelog(string $content, string $version): array
    {
        $lines = explode("\n", $content);
        $versionData = [
            'version' => $version,
            'date' => null,
            'added' => [],
            'changed' => [],
            'fixed' => [],
            'removed' => [],
            'security' => [],
            'raw' => '',
        ];

        $currentSection = null;
        $inVersionSection = false;
        $versionPattern = '/##\s*v?' . preg_quote($version, '/') . '/i';

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Check for version header
            if (preg_match($versionPattern, $line)) {
                $inVersionSection = true;
                // Extract date if present
                if (preg_match('/(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                    $versionData['date'] = $matches[1];
                }
                continue;
            }
            
            // Stop if we hit another version section
            if ($inVersionSection && preg_match('/^##\s*v?\d/', $line)) {
                break;
            }
            
            if (!$inVersionSection) {
                continue;
            }
            
            // Add to raw content
            $versionData['raw'] .= $line . "\n";
            
            // Parse sections (both ### and #### formats)
            if (preg_match('/^#{3,4}\s*(.+)$/i', $line, $matches)) {
                $sectionName = strtolower(trim($matches[1]));
                $currentSection = $this->mapSectionName($sectionName);
                continue;
            }
            
            // Parse list items
            if ($currentSection && preg_match('/^[-*]\s*(.+)$/', $line, $matches)) {
                $versionData[$currentSection][] = trim($matches[1]);
            }
        }

        // If no specific changelog found, generate from commits
        if (empty($versionData['raw'])) {
            return $this->getDefaultChangelog($version);
        }

        return $versionData;
    }

    /**
     * Map changelog section names to standardized keys.
     */
    protected function mapSectionName(string $section): ?string
    {
        $mapping = [
            'added' => 'added',
            'new' => 'added',
            'features' => 'added',
            'changed' => 'changed',
            'updated' => 'changed',
            'modified' => 'changed',
            'enhanced' => 'changed',
            'improved' => 'changed',  // Added for our changelog format
            'fixed' => 'fixed',
            'bugfixes' => 'fixed',
            'bug fixes' => 'fixed',
            'removed' => 'removed',
            'deleted' => 'removed',
            'security' => 'security',
            'technical details' => 'changed',  // Added for our format
        ];

        return $mapping[strtolower($section)] ?? null;
    }

    /**
     * Generate a default changelog from recent commits.
     */
    protected function getDefaultChangelog(string $version): array
    {
        $commits = $this->githubFileService->getRecentCommits(10);
        
        return [
            'version' => $version,
            'date' => date('Y-m-d'),
            'added' => [],
            'changed' => $this->extractCommitMessages($commits),
            'fixed' => [],
            'removed' => [],
            'security' => [],
            'raw' => "## v{$version} - " . date('Y-m-d') . "\n\n### Changed\n" . 
                     implode("\n", array_map(fn($msg) => "- {$msg}", $this->extractCommitMessages($commits))),
        ];
    }

    /**
     * Extract meaningful commit messages.
     */
    protected function extractCommitMessages(array $commits): array
    {
        $messages = [];
        
        foreach ($commits as $commit) {
            $message = $commit['message'];
            
            // Skip merge commits and version bumps
            if (strpos($message, 'Merge') === 0 || 
                strpos($message, 'Version') === 0 ||
                strpos($message, 'v1.') === 0) {
                continue;
            }
            
            // Clean up commit message
            $message = explode("\n", $message)[0]; // Get first line only
            $message = preg_replace('/^(feat|fix|chore|docs|style|refactor|test)(\(.+\))?:\s*/i', '', $message);
            
            if (strlen($message) > 10) { // Skip very short messages
                $messages[] = ucfirst($message);
            }
        }

        return array_slice($messages, 0, 10); // Limit to 10 items
    }

    /**
     * Get a formatted changelog for display in the UI.
     */
    public function getFormattedChangelog(string $version): string
    {
        $changelog = $this->getChangelogForVersion($version);
        $output = '';

        if (!empty($changelog['added'])) {
            $output .= "**New Features:**\n";
            foreach ($changelog['added'] as $item) {
                $output .= "• {$item}\n";
            }
            $output .= "\n";
        }

        if (!empty($changelog['changed'])) {
            $output .= "**Improvements:**\n";
            foreach ($changelog['changed'] as $item) {
                $output .= "• {$item}\n";
            }
            $output .= "\n";
        }

        if (!empty($changelog['fixed'])) {
            $output .= "**Bug Fixes:**\n";
            foreach ($changelog['fixed'] as $item) {
                $output .= "• {$item}\n";
            }
            $output .= "\n";
        }

        if (!empty($changelog['security'])) {
            $output .= "**Security:**\n";
            foreach ($changelog['security'] as $item) {
                $output .= "• {$item}\n";
            }
            $output .= "\n";
        }

        if (!empty($changelog['removed'])) {
            $output .= "**Removed:**\n";
            foreach ($changelog['removed'] as $item) {
                $output .= "• {$item}\n";
            }
        }

        return trim($output) ?: "Various improvements and bug fixes.";
    }

    /**
     * Get changelog summary for notification.
     */
    public function getChangelogSummary(string $version): array
    {
        $changelog = $this->getChangelogForVersion($version);
        
        return [
            'total_changes' => count($changelog['added']) + count($changelog['changed']) + 
                              count($changelog['fixed']) + count($changelog['removed']),
            'new_features' => count($changelog['added']),
            'improvements' => count($changelog['changed']),
            'bug_fixes' => count($changelog['fixed']),
            'preview' => $this->getChangelogPreview($changelog),
        ];
    }

    /**
     * Get a preview of the most important changes.
     */
    protected function getChangelogPreview(array $changelog): array
    {
        $preview = [];
        
        // Get top 3 items from each category
        foreach (['added', 'changed', 'fixed'] as $category) {
            $items = array_slice($changelog[$category], 0, 3);
            foreach ($items as $item) {
                $preview[] = $item;
            }
        }

        return array_slice($preview, 0, 5); // Max 5 items for preview
    }
}