<?php

namespace PterodactylAddons\ModManager\Commands;

use Illuminate\Console\Command;
use PterodactylAddons\ModManager\Services\CurseForgeApiService;
use PterodactylAddons\ModManager\Models\Mod;
use PterodactylAddons\ModManager\Models\ModFile;
use Illuminate\Support\Facades\Log;

class BenchmarkFileProcessingCommand extends Command
{
    protected $signature = 'mod-manager:benchmark-files
                           {--limit=50 : Number of mods to benchmark}
                           {--method=optimized : Method to test (legacy|optimized)}
                           {--dry-run : Run without database updates}';

    protected $description = 'Benchmark file processing performance with old vs new methods';

    private CurseForgeApiService $curseForgeService;

    public function __construct(CurseForgeApiService $curseForgeService)
    {
        parent::__construct();
        $this->curseForgeService = $curseForgeService;
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $method = $this->option('method');
        $isDryRun = $this->option('dry-run');

        $this->info("🚀 Benchmarking File Processing Performance");
        $this->info("Method: {$method} | Limit: {$limit} mods | Dry Run: " . ($isDryRun ? 'Yes' : 'No'));
        $this->line('');

        // Get random sample of mods for testing
        $mods = Mod::inRandomOrder()->limit($limit)->get();
        
        if ($mods->isEmpty()) {
            $this->error("No mods found in database. Please run harvesting first.");
            return 1;
        }

        $this->info("📊 Testing with " . $mods->count() . " mods");
        $this->line('');

        $startTime = microtime(true);
        $processedFiles = 0;
        $apiCalls = 0;
        $errors = 0;

        if ($method === 'optimized') {
            $this->info("🎯 Using REVOLUTIONARY BULK PROCESSING (getBulkModFilesOptimized)");
            $result = $this->benchmarkOptimizedMethod($mods, $isDryRun);
        } else {
            $this->info("🐌 Using LEGACY INDIVIDUAL PROCESSING (one-by-one)");
            $result = $this->benchmarkLegacyMethod($mods, $isDryRun);
        }

        $totalTime = microtime(true) - $startTime;
        
        // Display Results
        $this->line('');
        $this->info("📈 BENCHMARK RESULTS");
        $this->info("===================");
        $this->info("Method: {$method}");
        $this->info("Total Time: " . round($totalTime, 2) . "s");
        $this->info("Mods Processed: " . $result['mods_processed']);
        $this->info("Files Collected: " . $result['files_collected']);
        $this->info("API Calls Made: " . $result['api_calls']);
        $this->info("Errors: " . $result['errors']);
        $this->info("Avg Time per Mod: " . round($totalTime / $mods->count(), 3) . "s");
        
        if ($result['api_calls'] > 0) {
            $this->info("Avg Time per API Call: " . round($totalTime / $result['api_calls'], 3) . "s");
        }

        // Performance Analysis
        $this->line('');
        $this->comment("💡 PERFORMANCE ANALYSIS");
        
        if ($method === 'optimized') {
            $estimatedLegacyTime = $mods->count() * 1.2; // 1.2s per mod in legacy
            $speedImprovement = $estimatedLegacyTime / $totalTime;
            $this->info("✅ Optimized processing completed!");
            $this->info("🚀 Estimated speed improvement: " . round($speedImprovement, 1) . "x faster");
            $this->info("💰 Time saved: " . round($estimatedLegacyTime - $totalTime, 1) . "s");
        } else {
            $this->warn("⚠️  Legacy method is slow due to 1200ms delays between calls");
            $this->info("🔄 Run with --method=optimized to see the improvement!");
        }

        return 0;
    }

    private function benchmarkOptimizedMethod($mods, bool $isDryRun): array
    {
        $modIds = $mods->pluck('curse_mod_id')->toArray();
        
        $this->info("🚀 Using getBulkModFilesOptimized with " . count($modIds) . " mods");
        $this->line('');

        try {
            $result = $this->curseForgeService->getBulkModFilesOptimized($modIds);
            
            $filesCollected = count($result['data']);
            $stats = $result['stats'];
            
            $this->info("✅ Bulk collection completed!");
            $this->info("   Processed: {$stats['processed']}/{$stats['successful']} mods");
            $this->info("   Files: {$stats['total_files']} collected");
            $this->info("   Failed: {$stats['failed']} mods");
            
            // Simulate database operations (if not dry run)
            if (!$isDryRun) {
                $this->info("💾 Updating database...");
                $this->updateDatabaseFiles($result['data'], $mods);
            }

            return [
                'mods_processed' => $stats['processed'],
                'files_collected' => $stats['total_files'],
                'api_calls' => $stats['processed'], // One call per mod with optimized delays
                'errors' => $stats['failed']
            ];
            
        } catch (\Exception $e) {
            $this->error("❌ Bulk processing failed: " . $e->getMessage());
            Log::error("Benchmark bulk processing error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'mods_processed' => 0,
                'files_collected' => 0,
                'api_calls' => 0,
                'errors' => 1
            ];
        }
    }

    private function benchmarkLegacyMethod($mods, bool $isDryRun): array
    {
        $processedFiles = 0;
        $apiCalls = 0;
        $errors = 0;
        
        $this->info("🐌 Processing mods individually with 1200ms delays...");
        $progressBar = $this->output->createProgressBar($mods->count());
        $progressBar->start();

        foreach ($mods as $mod) {
            try {
                // Legacy method: individual API call with 1200ms delay
                usleep(1200 * 1000); // 1200ms delay (legacy behavior)
                
                $filesResponse = $this->curseForgeService->getModFiles($mod->curse_mod_id);
                $apiCalls++;
                
                if (isset($filesResponse['data']) && is_array($filesResponse['data'])) {
                    $processedFiles += count($filesResponse['data']);
                    
                    // Simulate database updates (if not dry run)
                    if (!$isDryRun) {
                        $this->updateModFiles($mod, $filesResponse['data']);
                    }
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("Error processing mod {$mod->curse_mod_id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->line('');

        return [
            'mods_processed' => $mods->count(),
            'files_collected' => $processedFiles,
            'api_calls' => $apiCalls,
            'errors' => $errors
        ];
    }

    private function updateDatabaseFiles(array $files, $mods): void
    {
        $modMap = $mods->pluck('id', 'curse_mod_id')->toArray();
        $updated = 0;
        $created = 0;

        foreach ($files as $fileData) {
            $curseModId = $fileData['source_mod_id'] ?? null;
            $modId = $modMap[$curseModId] ?? null;
            
            if (!$modId) continue;

            $file = ModFile::updateOrCreate(
                ['curse_file_id' => $fileData['id']],
                [
                    'mod_id' => $modId,
                    'display_name' => $fileData['displayName'],
                    'file_name' => $fileData['fileName'],
                    'release_type' => $fileData['releaseType'] ?? 1,
                    'file_status' => $fileData['fileStatus'] ?? 1,
                    'is_available' => $fileData['isAvailable'] ?? true,
                    'download_url' => $fileData['downloadUrl'] ?? null,
                    'file_length' => $fileData['fileLength'] ?? 0,
                    'download_count' => $fileData['downloadCount'] ?? 0,
                    'game_versions' => $fileData['gameVersions'] ?? [],
                    'file_date' => isset($fileData['fileDate']) ? \Carbon\Carbon::parse($fileData['fileDate']) : null,
                ]
            );
            
            $file->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->info("   Database: {$created} new files, {$updated} updated");
    }

    private function updateModFiles($mod, array $files): void
    {
        foreach ($files as $fileData) {
            ModFile::updateOrCreate(
                ['curse_file_id' => $fileData['id']],
                [
                    'mod_id' => $mod->id,
                    'display_name' => $fileData['displayName'],
                    'file_name' => $fileData['fileName'],
                    'release_type' => $fileData['releaseType'] ?? 1,
                    'file_status' => $fileData['fileStatus'] ?? 1,
                ]
            );
        }
    }
}