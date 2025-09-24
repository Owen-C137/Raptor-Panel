<?php

namespace PterodactylAddons\ModManager\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use PterodactylAddons\ModManager\Models\Game;
use PterodactylAddons\ModManager\Models\Mod;
use PterodactylAddons\ModManager\Models\ModFile;
use PterodactylAddons\ModManager\Models\DirectHarvestLog;
use PterodactylAddons\ModManager\Models\HarvestSkippedItem;
use PterodactylAddons\ModManager\Services\CurseForgeApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DirectHarvestController extends Controller
{
    protected $curseForgeService;
    
    public function __construct(CurseForgeApiService $curseForgeService)
    {
        $this->curseForgeService = $curseForgeService;
    }

    /**
     * Start direct harvest with streaming progress
     */
    public function harvestComplete(Request $request)
    {
        $gameId = $request->input('game_id'); // This is the internal database ID
        $harvestType = $request->input('type', 'complete');
        
        // Find the game by internal ID
        $game = Game::find($gameId);
        if (!$game) {
            return response()->json(['error' => 'Game not found'], 404);
        }

        // Create harvest session log
        $sessionId = 'direct-' . time() . '-' . uniqid();
        $log = DirectHarvestLog::create([
            'session_id' => $sessionId,
            'session_name' => "Direct {$harvestType} Harvest - {$game->name}",
            'harvest_type' => $harvestType,
            'user_id' => auth()->id(),
            'game_id' => $game->id,
            // Using 'running' immediately (enum doesn't include 'starting')
            'status' => 'running',
            'parameters' => json_encode([
                'curse_game_id' => $game->curse_game_id,
                'harvest_type' => $harvestType,
                'page_size' => 50
            ]),
            'started_at' => now()
        ]);

        // Stream the harvest process
        return response()->stream(function () use ($log, $game, $harvestType) {
            // Attempt to disable all output buffering for real-time streaming
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', 'off');
            @ini_set('implicit_flush', '1');
            while (ob_get_level() > 0) { @ob_end_flush(); }
            @ob_implicit_flush(1);

            // Send an initial heartbeat so the browser displays the stream area quickly
            echo "⏱️ Initializing harvest stream...\n";
            flush();

            $this->streamDirectHarvest($log, $game, $harvestType);
        }, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Accel-Buffering' => 'no', // Nginx: disable response buffering
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Stream the direct harvest process with real-time updates
     */
    protected function streamDirectHarvest(DirectHarvestLog $log, Game $game, string $harvestType)
    {
        try {
            // Send initial status
            echo "🚀 Starting Direct Harvest: {$harvestType} for {$game->name}\n";
            echo "📊 Session: {$log->session_id}\n";
            flush();

            // Update log status
            $log->update(['status' => 'running']);

            // Handle category-based harvesting specially (bypasses 10K limit)
            if ($harvestType === 'categories') {
                return $this->streamCategoryHarvest($log, $game);
            }

            // Determine harvest parameters based on type
            $params = array_merge($this->getHarvestParameters($harvestType), [
                'gameId' => $game->curse_game_id
            ]);

            $maxPages = (int) (config('mod-manager.harvest.max_pages', 2000)); // configurable safeguard
            $pageSize = 50;
            
            $processedMods = 0;
            $processedFiles = 0;
            $newMods = 0;
            $updatedMods = 0;
            $newFiles = 0;
            $updatedFiles = 0;
            $apiCalls = 0;
            $currentPage = 0;
            $totalMods = 0;
            
            // Initialize batch processing array for files
            $batchModIds = [];

            // Start harvesting
            echo "🔍 Fetching mod data from CurseForge...\n";
            flush();

            do {
                $currentIndex = $currentPage * $pageSize;
                
                // Check CurseForge API limit: (index + pageSize <= 10,000)
                if ($currentIndex + $pageSize > 10000) {
                    echo "⚠️  Reached CurseForge API limit (10,000 items max). Cannot fetch more mods.\n";
                    echo "ℹ️  To get more mods, use category-specific searches or different sort orders.\n";
                    flush();
                    break;
                }
                
                // Make API call
                $response = $this->curseForgeService->searchMods(array_merge($params, [
                    'index' => $currentIndex,
                    'pageSize' => $pageSize
                ]));
                $apiCalls++;

                if (!isset($response['data']) || empty($response['data'])) {
                    echo "⚠️  No more data available\n";
                    break;
                }

                $mods = $response['data'];
                $totalMods = $response['pagination']['totalCount'] ?? count($mods);
                
                echo "📄 Processing page " . ($currentPage + 1) . " - Batch size: " . count($mods) . " (Total reported: {$totalMods})\n";
                flush();

                // Persist total_mods on first page so UI can show target
                if ($currentPage === 0 && $totalMods > 0 && (int)$log->total_mods === 0) {
                    $log->update(['total_mods' => $totalMods]);
                }

                // Process each mod
                foreach ($mods as $modData) {
                    // Cancellation check before each mod
                    if (Cache::get('mod-manager:stop:' . $log->session_id) || $log->fresh()->status === 'stopping') {
                        echo "🛑 Stop signal received. Finalizing...\n"; flush();
                        
                        // Check if this is a force stop (skip file processing)
                        $isForceStop = Cache::get('mod-manager:force-stop:' . $log->session_id);
                        
                        if (!$isForceStop) {
                            // Normal stop - process any collected batch files before stopping
                            $filesStrategy = config('mod-manager.harvest.files_fetch_strategy', 'all');
                            if ($filesStrategy === 'batch' && isset($batchModIds) && !empty($batchModIds)) {
                                echo "\n🚀 Processing " . count($batchModIds) . " collected mods for files before stopping...\n";
                                flush();
                                
                                $batchProcessed = $this->processBatchFiles($batchModIds, $apiCalls, $processedFiles, $newFiles, $updatedFiles, $log);
                                $apiCalls = $batchProcessed['api_calls'];
                                $processedFiles = $batchProcessed['processed_files'];
                                $newFiles = $batchProcessed['new_files'];
                                $updatedFiles = $batchProcessed['updated_files'];
                                
                                echo "✅ Batch file processing completed!\n";
                                flush();
                            }
                        } else {
                            echo "🚨 Force stop requested - skipping file processing\n";
                            flush();
                        }
                        
                        $duration = now()->diffInSeconds($log->started_at);
                        $modsPerSecond = $duration > 0 ? round($processedMods / max($duration,1), 2) : 0;
                        $log->update([
                            'status' => $isForceStop ? 'force_stopped' : 'stopped',
                            'processed_mods' => $processedMods,
                            'processed_files' => $processedFiles,
                            'total_files' => $processedFiles,
                            'api_calls_made' => $apiCalls,
                            'duration_seconds' => $duration,
                            'mods_per_second' => $modsPerSecond,
                            'completed_at' => now(),
                            'new_mods' => $newMods,
                            'updated_mods' => $updatedMods,
                            'new_files' => $newFiles,
                            'updated_files' => $updatedFiles,
                            'error_count' => HarvestSkippedItem::where('session_id', $log->session_id)->count(),
                        ]);
                        echo "🏁 " . ($isForceStop ? "Force stopped" : "Stopped") . " at {$processedMods} mods / {$processedFiles} files after {$duration}s\n"; flush();
                        
                        // Clean up cache entries
                        Cache::forget('mod-manager:stop:' . $log->session_id);
                        Cache::forget('mod-manager:force-stop:' . $log->session_id);
                        
                        return; // exit early
                    }
                    try {
                        // Save/update mod
                        $mod = Mod::updateOrCreate(
                            ['curse_mod_id' => $modData['id']],
                            [
                                'game_id' => $game->id,
                                'name' => $modData['name'],
                                'slug' => $modData['slug'],
                                'summary' => $modData['summary'] ?? null,
                                'description' => $modData['description'] ?? null,
                                'download_count' => $modData['downloadCount'] ?? 0,
                                'popularity_rank' => $modData['gamePopularityRank'] ?? null,
                                'thumbs_up_count' => $modData['thumbsUpCount'] ?? 0,
                                'rating' => $modData['rating'] ?? null,
                                'logo_url' => $modData['logo']['url'] ?? null,
                                // rely on casts to JSON
                                'screenshots' => $modData['screenshots'] ?? [],
                                'authors' => $modData['authors'] ?? [],
                                'categories' => array_column($modData['categories'] ?? [], 'id'),
                                'website_url' => $modData['links']['websiteUrl'] ?? null,
                                'wiki_url' => $modData['links']['wikiUrl'] ?? null,
                                'issues_url' => $modData['links']['issuesUrl'] ?? null,
                                'source_url' => $modData['links']['sourceUrl'] ?? null,
                                'date_created' => isset($modData['dateCreated']) ? \Carbon\Carbon::parse($modData['dateCreated']) : null,
                                'date_modified' => isset($modData['dateModified']) ? \Carbon\Carbon::parse($modData['dateModified']) : null,
                                'date_released' => isset($modData['dateReleased']) ? \Carbon\Carbon::parse($modData['dateReleased']) : null,
                                'allow_mod_distribution' => $modData['allowModDistribution'] ?? true,
                                'is_available' => $modData['isAvailable'] ?? true,
                                'game_popularity_rank' => $modData['gamePopularityRank'] ?? null,
                                'last_sync_at' => now(),
                                'sync_status' => 'completed'
                            ]
                        );

                        $mod->wasRecentlyCreated ? $newMods++ : $updatedMods++;
                        $processedMods++;

                        // Optional pivot sync (config flag)
                        if (config('mod-manager.harvest.sync_category_pivot', false) && !empty($modData['categories'])) {
                            $categoryIds = array_column($modData['categories'], 'id');
                            try {
                                $mod->categories()->syncWithoutDetaching($categoryIds);
                            } catch (\Throwable $e) {
                                Log::warning('Category pivot sync failed', ['mod_id' => $mod->id, 'error' => $e->getMessage()]);
                            }
                        }

                        // Decide whether to fetch files for this mod (performance strategy)
                        $filesStrategy = config('mod-manager.harvest.files_fetch_strategy', 'all');
                        $shouldFetchFiles = false;
                        if ($harvestType === 'complete') {
                            if ($filesStrategy === 'all') {
                                $shouldFetchFiles = true;
                            } elseif ($filesStrategy === 'new' && $mod->wasRecentlyCreated) {
                                $shouldFetchFiles = true;
                            } elseif ($filesStrategy === 'batch') {
                                // For batch strategy, collect mod IDs for later batch processing
                                $batchModIds[] = $mod->id;
                                $shouldFetchFiles = false; // Will process in batch later
                                
                                // Debug output every 50 mods
                                if (count($batchModIds) % 50 === 0) {
                                    echo "📦 Collected " . count($batchModIds) . " mod IDs for batch file processing...\n";
                                    flush();
                                }
                            } elseif ($filesStrategy === 'none') {
                                $shouldFetchFiles = false; // explicit
                            }
                        }

                        if ($shouldFetchFiles) {
                            $filesResponse = $this->curseForgeService->getModFiles($modData['id']);
                            $apiCalls++;
                            
                            if (isset($filesResponse['data'])) {
                                foreach ($filesResponse['data'] as $fileData) {
                                    $file = ModFile::updateOrCreate(
                                        ['curse_file_id' => $fileData['id']],
                                        [
                                            'mod_id' => $mod->id,
                                            'display_name' => $fileData['displayName'],
                                            'file_name' => $fileData['fileName'],
                                            'release_type' => $fileData['releaseType'] ?? 1,
                                            'file_status' => $fileData['fileStatus'] ?? 1,
                                            'is_available' => $fileData['isAvailable'] ?? true,
                                            'download_url' => $fileData['downloadUrl'] ?? null,
                                            'file_length' => $fileData['fileLength'] ?? 0,
                                            'download_count' => $fileData['downloadCount'] ?? 0,
                                            'file_size_on_disk' => $fileData['fileSizeOnDisk'] ?? 0,
                                            'game_versions' => $fileData['gameVersions'] ?? [],
                                            'sortable_game_versions' => $fileData['sortableGameVersions'] ?? [],
                                            'mod_loader_types' => $fileData['modLoaders'] ?? [],
                                            'dependencies' => $fileData['dependencies'] ?? [],
                                            'hashes' => $fileData['hashes'] ?? [],
                                            'file_fingerprint' => $fileData['fileFingerprint'] ?? null,
                                            'modules' => $fileData['modules'] ?? [],
                                            'file_date' => isset($fileData['fileDate']) ? \Carbon\Carbon::parse($fileData['fileDate']) : null,
                                            'upload_date' => isset($fileData['uploadDate']) ? \Carbon\Carbon::parse($fileData['uploadDate']) : null,
                                            'is_server_pack' => $fileData['isServerPack'] ?? false,
                                            'server_pack_file_id' => $fileData['serverPackFileId'] ?? null,
                                            'is_early_access_content' => $fileData['isEarlyAccessContent'] ?? false,
                                            'early_access_end_date' => isset($fileData['earlyAccessEndDate']) ? \Carbon\Carbon::parse($fileData['earlyAccessEndDate']) : null,
                                            'expose_as_alternative' => $fileData['exposeAsAlternative'] ?? false,
                                            'parent_project_file_id' => $fileData['parentProjectFileId'] ?? null,
                                            'alternate_file_id' => $fileData['alternateFileId'] ?? null
                                        ]
                                    );
                                    $processedFiles++;
                                    $file->wasRecentlyCreated ? $newFiles++ : $updatedFiles++;
                                }
                                // Check meta skip
                                if (isset($filesResponse['_meta']['skipped_reason'])) {
                                    HarvestSkippedItem::create([
                                        'harvest_log_id' => $log->id,
                                        'session_id' => $log->session_id,
                                        'item_type' => 'file',
                                        'curse_id' => null,
                                        'parent_curse_mod_id' => $modData['id'],
                                        'reason_code' => $filesResponse['_meta']['skipped_reason'],
                                        'http_status' => $filesResponse['_meta']['status'] ?? null,
                                        'endpoint' => $filesResponse['_meta']['endpoint'] ?? null,
                                        'message' => 'File list forbidden for this mod',
                                    ]);
                                }
                            }
                        }

                        // Progress update every 25 mods (less chatter, better throughput)
                        if ($processedMods % 25 === 0) {
                            echo "📥 Processed {$processedMods}/{$totalMods} mods ({$processedFiles} files) - API calls: {$apiCalls}\n";
                            flush();
                            // Periodic DB progress persistence every 100 mods to reduce write load further
                            if ($processedMods % 100 === 0) {
                                $log->update([
                                    'processed_mods' => $processedMods,
                                    'processed_files' => $processedFiles,
                                    'api_calls_made' => $apiCalls,
                                    'total_files' => $processedFiles,
                                    'new_mods' => $newMods,
                                    'updated_mods' => $updatedMods,
                                    'new_files' => $newFiles,
                                    'updated_files' => $updatedFiles,
                                ]);
                            }
                        }

                    } catch (\Exception $e) {
                        echo "⚠️  Error processing mod {$modData['name']}: " . $e->getMessage() . "\n";
                        Log::error('Mod processing error', ['mod' => $modData['id'], 'error' => $e->getMessage()]);
                        HarvestSkippedItem::create([
                            'harvest_log_id' => $log->id,
                            'session_id' => $log->session_id,
                            'item_type' => 'mod',
                            'curse_id' => $modData['id'] ?? null,
                            'parent_curse_mod_id' => null,
                            'reason_code' => 'mod_exception',
                            'http_status' => null,
                            'endpoint' => null,
                            'message' => Str::limit($e->getMessage(), 500),
                        ]);
                        $log->increment('error_count');
                        flush();
                    }
                }

                $currentPage++;
                
                // Stop conditions
                if ($harvestType === 'popular' && $processedMods >= (int)config('mod-manager.harvest.popular_limit', 1000)) break;
                if ($harvestType === 'recent' && $processedMods >= (int)config('mod-manager.harvest.recent_limit', 500)) break;
                
                // Rate limiting
                if ((bool)config('mod-manager.harvest.page_sleep_enabled', true)) {
                    usleep((int) (config('mod-manager.harvest.page_sleep_microseconds', 300000))); // default 0.3s
                }
                
            } while ($currentPage * $pageSize < $totalMods && $currentPage < $maxPages);

            // Process batch file fetching if strategy was 'batch'
            $filesStrategy = config('mod-manager.harvest.files_fetch_strategy', 'all');
            echo "\n🔧 Files strategy: {$filesStrategy}, Batch mod IDs collected: " . (isset($batchModIds) ? count($batchModIds) : 0) . "\n";
            flush();
            
            if ($filesStrategy === 'batch' && isset($batchModIds) && !empty($batchModIds)) {
                echo "\n🚀 Starting batch file processing for " . count($batchModIds) . " mods...\n";
                flush();
                
                $batchProcessed = $this->processBatchFiles($batchModIds, $apiCalls, $processedFiles, $newFiles, $updatedFiles, $log);
                $apiCalls = $batchProcessed['api_calls'];
                $processedFiles = $batchProcessed['processed_files'];
                $newFiles = $batchProcessed['new_files'];
                $updatedFiles = $batchProcessed['updated_files'];
                
                echo "✅ Batch file processing completed!\n";
                flush();
            }

            // Final statistics
            $duration = now()->diffInSeconds($log->started_at);
            $modsPerSecond = $duration > 0 ? round($processedMods / $duration, 2) : 0;

            echo "\n🎉 Harvest Complete!\n";
            echo "📊 Final Stats:\n";
            echo "   • Total Mods: {$processedMods}\n";
            echo "   • New Mods: {$newMods}\n";
            echo "   • Updated Mods: {$updatedMods}\n";
            echo "   • Total Files: {$processedFiles}\n";
            echo "   • New Files: {$newFiles}\n";
            echo "   • API Calls Made: {$apiCalls}\n";
            echo "   • Duration: {$duration}s\n";
            echo "   • Speed: {$modsPerSecond} mods/second\n";
            
            if ($processedMods >= 200) { // About 10,000 items limit reached (50 per page = 200 pages)
                echo "\n⚠️  CurseForge API Limit:\n";
                echo "   • Hit 10,000 item pagination limit\n";
                echo "   • Use category searches for more mods\n";
            }

            // Update log with final stats
            $log->update([
                'status' => 'completed',
                'total_mods' => $processedMods,
                'total_files' => $processedFiles,
                'processed_mods' => $processedMods,
                'processed_files' => $processedFiles,
                'api_calls_made' => $apiCalls,
                'completed_at' => now(),
                'duration_seconds' => $duration,
                'mods_per_second' => $modsPerSecond,
                'new_mods' => $newMods,
                'updated_mods' => $updatedMods,
                'new_files' => $newFiles,
                'updated_files' => $updatedFiles,
                'error_count' => HarvestSkippedItem::where('session_id', $log->session_id)->count(),
            ]);

            // Clean up cache entries on successful completion
            Cache::forget('mod-manager:stop:' . $log->session_id);
            Cache::forget('mod-manager:force-stop:' . $log->session_id);

            echo "\n✅ Session logged successfully!\n";
            flush();

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            echo "\n❌ Harvest Failed: {$errorMessage}\n";
            
            $log->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'completed_at' => now()
            ]);
            
            // Clean up cache entries on error too
            Cache::forget('mod-manager:stop:' . $log->session_id);
            Cache::forget('mod-manager:force-stop:' . $log->session_id);
            
            Log::error('Direct harvest failed', ['error' => $errorMessage, 'log_id' => $log->id]);
            flush();
        }
    }

    /**
     * Get harvest parameters based on type
     */
    protected function getHarvestParameters(string $type): array
    {
        switch ($type) {
            case 'popular':
                return [
                    'sortField' => 6, // Downloads
                    'sortOrder' => 'desc'
                ];
            case 'recent':
                return [
                    'sortField' => 12, // Last Updated
                    'sortOrder' => 'desc'
                ];
            case 'categories':
                return [
                    'sortField' => 1, // Featured (will be overridden per category)
                    'sortOrder' => 'desc'
                ];
            case 'complete':
            default:
                return [
                    'sortField' => 1, // Featured
                    'sortOrder' => 'desc'
                ];
        }
    }

    /**
     * Stop current harvest (placeholder - streaming stops when connection closes)
     */
    public function stopHarvest(Request $request)
    {
        $sessionId = $request->input('session_id');
        if (!$sessionId) {
            return response()->json(['error' => 'session_id required'], 422);
        }
        $log = DirectHarvestLog::where('session_id', $sessionId)->first();
        if (!$log) {
            return response()->json(['error' => 'Session not found'], 404);
        }
        if (!in_array($log->status, ['running'])) {
            return response()->json(['message' => 'Session not in running state', 'status' => $log->status]);
        }
        
        // Check if this is a force stop (skip file processing)
        $skipFiles = $request->boolean('skip_files', false);
        $reason = $request->input('reason', 'user_request');
        
        if ($skipFiles || $reason === 'force_stop') {
            // Force stop - set cache flags to stop immediately and skip files
            Cache::put("harvest_stop_{$sessionId}", true, now()->addMinutes(10));
            Cache::put("harvest_stop_type_{$sessionId}", 'force', now()->addMinutes(10));
            $log->update(['status' => 'stopping']);
            $message = 'Force stop signal sent - will stop immediately and skip file processing';
        } else {
            // Graceful stop - will finish current work and process files for collected mods
            Cache::put("harvest_stop_{$sessionId}", true, now()->addMinutes(10));
            Cache::put("harvest_stop_type_{$sessionId}", 'graceful', now()->addMinutes(10));
            $log->update(['status' => 'stopping']);  
            $message = 'Graceful stop signal sent - will finish current category and process files';
        }
        return response()->json(['message' => $message, 'session_id' => $sessionId, 'force_stop' => $skipFiles]);
    }
    
    /**
     * Process files for mods in batches (batch strategy)
     */
    private function processBatchFiles(array $modIds, int $apiCalls, int $processedFiles, int $newFiles, int $updatedFiles, $log = null): array
    {
        $batchSize = config('mod-manager.harvest.files_batch_size', 50);
        $totalMods = count($modIds);
        $processedCount = 0;
        $startTime = microtime(true);

        echo "🔧 Starting batch file processing for {$totalMods} mods in batches of {$batchSize}\n";
        flush();

        // Process mods in chunks
        foreach (array_chunk($modIds, $batchSize) as $batchNum => $batch) {
            $batchStartTime = microtime(true);
            echo "📦 Starting batch " . ($batchNum + 1) . " with " . count($batch) . " mods\n";
            flush();
            
            foreach ($batch as $curseModId) {
                try {
                    // Find mod by CurseForge ID, not database ID
                    $curseMod = \PterodactylAddons\ModManager\Models\Mod::where('curse_mod_id', $curseModId)->first();
                    if (!$curseMod) {
                        continue;
                    }

                    // Enhanced rate limiting for batch processing (configurable)
                    $batchDelay = config('mod-manager.harvest.batch_api_delay_ms', 1200); // Default 1.2s in ms
                    usleep($batchDelay * 1000); // Convert ms to microseconds
                    
                    $filesResponse = $this->curseForgeService->getModFiles($curseMod->curse_mod_id);
                    $apiCalls++;
                    
                    if (isset($filesResponse['data'])) {
                        foreach ($filesResponse['data'] as $fileData) {
                            $file = \PterodactylAddons\ModManager\Models\ModFile::updateOrCreate(
                                ['curse_file_id' => $fileData['id']],
                                [
                                    'mod_id' => $curseMod->id,
                                    'display_name' => $fileData['displayName'],
                                    'file_name' => $fileData['fileName'],
                                    'release_type' => $fileData['releaseType'] ?? 1,
                                    'file_status' => $fileData['fileStatus'] ?? 1,
                                    'is_available' => $fileData['isAvailable'] ?? true,
                                    'download_url' => $fileData['downloadUrl'] ?? null,
                                    'file_length' => $fileData['fileLength'] ?? 0,
                                    'download_count' => $fileData['downloadCount'] ?? 0,
                                    'file_size_on_disk' => $fileData['fileSizeOnDisk'] ?? 0,
                                    'game_versions' => $fileData['gameVersions'] ?? [],
                                    'sortable_game_versions' => $fileData['sortableGameVersions'] ?? [],
                                    'mod_loader_types' => $fileData['modLoaders'] ?? [],
                                    'dependencies' => $fileData['dependencies'] ?? [],
                                    'hashes' => $fileData['hashes'] ?? [],
                                    'file_fingerprint' => $fileData['fileFingerprint'] ?? null,
                                    'modules' => $fileData['modules'] ?? [],
                                    'file_date' => isset($fileData['fileDate']) ? \Carbon\Carbon::parse($fileData['fileDate']) : null,
                                    'upload_date' => isset($fileData['uploadDate']) ? \Carbon\Carbon::parse($fileData['uploadDate']) : null,
                                    'is_server_pack' => $fileData['isServerPack'] ?? false,
                                    'server_pack_file_id' => $fileData['serverPackFileId'] ?? null,
                                    'is_early_access_content' => $fileData['isEarlyAccessContent'] ?? false,
                                    'early_access_end_date' => isset($fileData['earlyAccessEndDate']) ? \Carbon\Carbon::parse($fileData['earlyAccessEndDate']) : null,
                                    'expose_as_alternative' => $fileData['exposeAsAlternative'] ?? false,
                                    'parent_project_file_id' => $fileData['parentProjectFileId'] ?? null,
                                    'alternate_file_id' => $fileData['alternateFileId'] ?? null
                                ]
                            );
                            $processedFiles++;
                            $file->wasRecentlyCreated ? $newFiles++ : $updatedFiles++;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Batch file processing error for mod {$curseModId}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // If it's a rate limit or network error, add extra delay
                    if (strpos($e->getMessage(), 'rate') !== false || 
                        strpos($e->getMessage(), 'network') !== false ||
                        strpos($e->getMessage(), 'timeout') !== false) {
                        echo "⚠️  Rate limit detected, adding delay...\n";
                        flush();
                        usleep(5000000); // 5 second delay for rate limit errors
                    }
                }
                
                $processedCount++;
                
                // Progress update every 5 mods for more responsive feedback
                if ($processedCount % 5 === 0 || $processedCount === $totalMods) {
                    $elapsed = microtime(true) - $startTime;
                    $rate = $processedCount / $elapsed;
                    $eta = $rate > 0 ? ($totalMods - $processedCount) / $rate : 0;
                    
                    echo "📁 Batch processing: {$processedCount}/{$totalMods} mods ({$processedFiles} files) - API calls: {$apiCalls} - ETA: " . round($eta) . "s\n";
                    flush();
                    
                    // Update harvest log progress if provided
                    if ($log) {
                        $log->update([
                            'processed_files' => $processedFiles,
                            'new_files' => $newFiles,
                            'updated_files' => $updatedFiles,
                            'api_calls_made' => $apiCalls,
                        ]);
                    }
                }
            }
            
            $batchElapsed = microtime(true) - $batchStartTime;
            echo "✅ Batch " . ($batchNum + 1) . " completed in " . round($batchElapsed, 1) . "s\n";
            flush();
            
            // Longer delay between batches to prevent API rate limiting (configurable)
            $batchPauseMs = config('mod-manager.harvest.batch_pause_ms', 2000); // Default 2s in ms
            usleep($batchPauseMs * 1000); // Convert ms to microseconds
        }

        return [
            'api_calls' => $apiCalls,
            'processed_files' => $processedFiles,
            'new_files' => $newFiles,
            'updated_files' => $updatedFiles
        ];
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $unit = strtoupper(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        switch ($unit) {
            case 'G': return $value * 1024 * 1024 * 1024;
            case 'M': return $value * 1024 * 1024;
            case 'K': return $value * 1024;
            default: return (int) $limit;
        }
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
    
    /**
     * Category-based harvest that bypasses the 10,000 item API limit
     * by harvesting each category separately
     */
    protected function streamCategoryHarvest(DirectHarvestLog $log, Game $game)
    {
        // Clean up any abandoned harvest sessions (running for > 2 hours without updates)
        $this->cleanupAbandonedSessions();
        
        $startTime = time();
        $allModIds = [];
        $processedMods = 0;
        $newMods = 0;
        $updatedMods = 0;
        $processedFiles = 0;
        $newFiles = 0;
        $updatedFiles = 0;
        $apiCalls = 0;
        
        try {
            echo "🏷️  Category-Based Harvest - Bypassing 10,000 Item Limit\n";
            echo "📂 Loading categories for {$game->name}...\n";
            flush();

            // Get all categories for this game
            $categories = \PterodactylAddons\ModManager\Models\Category::where('game_id', $game->id)->get();
            
            if ($categories->isEmpty()) {
                echo "📂 No categories found for {$game->name}. Fetching from CurseForge API...\n";
                flush();
                
                try {
                    // Fetch categories from CurseForge API
                    $categoriesResponse = $this->curseForgeService->getCategories($game->curse_game_id);
                    $apiCalls++;
                    
                    if (isset($categoriesResponse['data']) && !empty($categoriesResponse['data'])) {
                        echo "✅ Found " . count($categoriesResponse['data']) . " categories, importing...\n";
                        
                        foreach ($categoriesResponse['data'] as $cat) {
                            \PterodactylAddons\ModManager\Models\Category::updateOrCreate(
                                ['curse_category_id' => $cat['id']],
                                [
                                    'curse_category_id' => $cat['id'],
                                    'game_id' => $game->id,
                                    'name' => $cat['name'],
                                    'slug' => $cat['slug'] ?? \Str::slug($cat['name']),
                                    'url' => $cat['url'] ?? null,
                                    'icon_url' => $cat['iconUrl'] ?? null,
                                    'is_class' => $cat['isClass'] ?? false,
                                    'class_id' => $cat['classId'] ?? null,
                                    'parent_category_id' => $cat['parentCategoryId'] ?? null,
                                    'display_index' => $cat['displayIndex'] ?? 0,
                                ]
                            );
                        }
                        
                        // Reload categories
                        $categories = \PterodactylAddons\ModManager\Models\Category::where('game_id', $game->id)->get();
                        echo "✅ Successfully imported " . $categories->count() . " categories\n";
                        
                    } else {
                        echo "❌ Failed to fetch categories from CurseForge API\n";
                        return;
                    }
                    
                } catch (\Exception $e) {
                    echo "❌ Error fetching categories: " . $e->getMessage() . "\n";
                    return;
                }
            }

            $totalCategories = $categories->count();
            echo "📊 Found {$totalCategories} categories to process\n";
            echo "🚀 Starting category-by-category collection...\n\n";
            flush();

            $categoryCount = 0;
            
            foreach ($categories as $category) {
                $categoryCount++;
                
                // Check for stop signal
                if (Cache::has("harvest_stop_{$log->session_id}")) {
                    echo "\n⏹️  Stop signal received during category processing.\n";
                    break;
                }
                
                echo "📂 [{$categoryCount}/{$totalCategories}] Processing: {$category->name} (ID: {$category->curse_category_id})\n";
                flush();

                $categoryModsFound = 0;

                $pageSize = 50;
                $currentPage = 0;
                $maxPages = 200; // 200 pages * 50 = 10,000 items per category (API limit)
                
                do {
                    $currentIndex = $currentPage * $pageSize;
                    
                    // Check category-specific API limit
                    if ($currentIndex + $pageSize > 10000) {
                        echo "   ⚠️  Hit 10K limit for category '{$category->name}' - moving to next category\n";
                        break;
                    }

                    try {
                        // Check for stop signal before API call
                        if (Cache::has("harvest_stop_{$log->session_id}")) {
                            $stopType = Cache::get("harvest_stop_type_{$log->session_id}", 'graceful');
                            if ($stopType === 'force') {
                                echo "\n🛑 FORCE STOP received - stopping immediately without file collection.\n";
                            } else {
                                echo "\n⏹️  GRACEFUL STOP received - will process files for all " . count($allModIds) . " collected mods.\n";
                            }
                            break 2; // Break out of page loop and category loop
                        }

                        // Make API call for this category
                        $response = $this->curseForgeService->searchMods([
                            'gameId' => $game->curse_game_id,
                            'categoryId' => $category->curse_category_id,
                            'sortField' => 6, // Downloads
                            'sortOrder' => 'desc',
                            'index' => $currentIndex,
                            'pageSize' => $pageSize
                        ]);
                        $apiCalls++;

                        if (!isset($response['data']) || empty($response['data'])) {
                            echo "   📭 No mods found on page " . ($currentPage + 1) . " for category '{$category->name}'\n";
                            break; // No more data for this category
                        }

                        $mods = $response['data'];
                        $pageModCount = count($mods);
                        $categoryProgress = round(($currentPage / $maxPages) * 100, 1);
                        
                        echo "   📄 Page " . ($currentPage + 1) . "/{$maxPages} ({$categoryProgress}%): {$pageModCount} mods";
                        
                        // Show progress within this category
                        $categoryTotalSoFar = (($currentPage) * $pageSize) + $pageModCount;
                        echo " | Category total: {$categoryTotalSoFar}\n";

                        // Check for stop signal BEFORE processing mods
                        if (Cache::has("harvest_stop_{$log->session_id}")) {
                            $stopType = Cache::get("harvest_stop_type_{$log->session_id}", 'graceful');
                            if ($stopType === 'force') {
                                echo "\n🛑 FORCE STOP - stopping immediately.\n";
                            } else {
                                echo "\n⏹️  GRACEFUL STOP - will collect files for " . count($allModIds) . " mods.\n";
                            }
                            break 2; // Break out of page loop and category loop
                        }

                        // Process mods for this page
                        $pageNewMods = 0;
                        $pageUpdatedMods = 0;
                        $categoryModsFound += $pageModCount;
                        
                        foreach ($mods as $modData) {

                            $modId = $modData['id'];
                            $allModIds[] = $modId;

                            // Debug: Show first few mods being processed
                            if ($processedMods < 5) {
                                echo "     🔍 Processing mod: {$modData['name']} (ID: {$modId})\n";
                            }

                            // Create/update mod
                            try {
                                $mod = Mod::updateOrCreate(
                                    ['curse_mod_id' => $modId],
                                    [
                                        'game_id' => $game->id,
                                        'name' => $modData['name'],
                                        'slug' => $modData['slug'],
                                        'summary' => $modData['summary'] ?? null,
                                        'download_count' => $modData['downloadCount'] ?? 0,
                                        'thumbs_up_count' => $modData['thumbsUpCount'] ?? 0,
                                        'logo_url' => isset($modData['logo']) ? $modData['logo']['url'] ?? null : null,
                                        'authors' => array_column($modData['authors'] ?? [], 'name'),
                                        'categories' => array_column($modData['categories'] ?? [], 'id'),
                                        'website_url' => $modData['links']['websiteUrl'] ?? null,
                                        'wiki_url' => $modData['links']['wikiUrl'] ?? null,
                                        'issues_url' => $modData['links']['issuesUrl'] ?? null,
                                        'source_url' => $modData['links']['sourceUrl'] ?? null,
                                        'date_created' => isset($modData['dateCreated']) ? Carbon::parse($modData['dateCreated']) : null,
                                        'date_modified' => isset($modData['dateModified']) ? Carbon::parse($modData['dateModified']) : null,
                                        'date_released' => isset($modData['dateReleased']) ? Carbon::parse($modData['dateReleased']) : null,
                                        'allow_mod_distribution' => $modData['allowModDistribution'] ?? true,
                                        'game_popularity_rank' => $modData['gamePopularityRank'] ?? null,
                                        'is_available' => $modData['isAvailable'] ?? true,
                                        'last_sync_at' => now(),
                                        'sync_status' => 'completed'
                                    ]
                                );
                            } catch (\Exception $e) {
                                echo "     ❌ Error creating mod '{$modData['name']}': " . $e->getMessage() . "\n";
                                continue; // Skip this mod and continue
                            }

                            if ($mod->wasRecentlyCreated) {
                                $newMods++;
                                $pageNewMods++;
                            } else {
                                $updatedMods++;
                                $pageUpdatedMods++;
                            }
                            $processedMods++;
                        }
                        
                        // Update the harvest log with current progress
                        $log->update([
                            'total_mods' => count($allModIds),
                            'processed_mods' => $processedMods,
                            'api_calls_made' => $apiCalls,
                            'result_data' => json_encode([
                                'categories_processed' => $categoryCount,
                                'total_categories' => $totalCategories,
                                'current_category' => $category->name,
                                'current_page' => $currentPage + 1,
                                'new_mods' => $newMods,
                                'updated_mods' => $updatedMods,
                            ])
                        ]);
                        
                        // Show page completion stats
                        echo "     ✅ Page complete: +{$pageNewMods} new, ~{$pageUpdatedMods} updated";
                        if ($pageModCount > 0) {
                            $topMod = $mods[0];
                            echo " | Top mod: '{$topMod['name']}' ({$topMod['downloadCount']} downloads)";
                        }
                        echo "\n";

                        $currentPage++;
                        
                        // Add rate limiting delay
                        $delayMs = config('mod-manager.direct_harvest.batch_api_delay_ms', 1000);
                        if ($delayMs > 0) {
                            usleep($delayMs * 1000);
                        }

                    } catch (\Exception $e) {
                        echo "   ❌ API error for category '{$category->name}': " . $e->getMessage() . "\n";
                        break; // Move to next category
                    }
                    
                } while ($currentPage < $maxPages && !Cache::has("harvest_stop_{$log->session_id}"));

                // Check if we were stopped
                if (Cache::has("harvest_stop_{$log->session_id}")) {
                    echo "\n⏹️  Stopping category processing due to user request.\n";
                    echo "📂 Processed {$categoryCount}/{$totalCategories} categories before stopping.\n";
                    break; // Exit category loop
                }

                // Calculate category completion stats
                $categoryProgress = round(($categoryCount / $totalCategories) * 100, 1);
                
                // Verify mods were actually saved to database
                $totalModsInDb = Mod::count();
                
                if ($categoryModsFound === 0) {
                    echo "   📭 Category '{$category->name}' was empty (no mods found)\n";
                } else {
                    echo "   🎯 Category '{$category->name}' complete: {$categoryModsFound} mods processed\n";
                }
                echo "   💾 Database verification: {$totalModsInDb} total mods in database\n";
                echo "   📊 Overall progress: {$categoryCount}/{$totalCategories} categories ({$categoryProgress}%)\n";
                echo "   📈 Session totals: {$processedMods} processed, {$newMods} new, {$updatedMods} updated\n\n";
                flush();
                
                // Show detailed summary every 10 categories
                if ($categoryCount % 10 === 0) {
                    $elapsedTime = time() - $startTime;
                    $modsPerMinute = $elapsedTime > 0 ? round(($processedMods / $elapsedTime) * 60, 1) : 0;
                    $categoriesPerMinute = $elapsedTime > 0 ? round(($categoryCount / $elapsedTime) * 60, 1) : 0;
                    $estimatedTotal = $processedMods > 0 ? round(($processedMods / $categoryCount) * $totalCategories) : 0;
                    $etaMinutes = $categoriesPerMinute > 0 ? round(($totalCategories - $categoryCount) / $categoriesPerMinute) : 0;
                    
                    echo "🔥 === MILESTONE: {$categoryCount}/{$totalCategories} CATEGORIES COMPLETE ===\n";
                    echo "   ⏱️  Elapsed: " . gmdate('H:i:s', $elapsedTime) . " | Speed: {$modsPerMinute} mods/min, {$categoriesPerMinute} cats/min\n";
                    echo "   🎯 Projected total: ~{$estimatedTotal} mods | ETA: ~{$etaMinutes} minutes\n";
                    echo "   💾 Memory: " . $this->formatBytes(memory_get_usage(true)) . " / " . $this->formatBytes(memory_get_peak_usage(true)) . " peak\n";
                    echo "   🔄 API calls: {$apiCalls} total\n\n";
                    flush();
                }
                
                // Add pause between categories
                $pauseMs = config('mod-manager.direct_harvest.batch_pause_ms', 500);
                if ($pauseMs > 0) {
                    usleep($pauseMs * 1000);
                }
            }
            
            // Determine if harvest was stopped or completed normally
            $wasStopped = Cache::has("harvest_stop_{$log->session_id}");
            $stopType = Cache::get("harvest_stop_type_{$log->session_id}", 'graceful');
            $wasCompleted = ($categoryCount >= $totalCategories);
            
            // FAILSAFE: Update status early to ensure it's recorded even if later code fails
            $currentDuration = now()->diffInSeconds($log->started_at);
            $currentModsPerSecond = $currentDuration > 0 ? round($processedMods / $currentDuration, 2) : 0;
            $preliminaryStatus = $wasStopped ? (($stopType === 'force') ? 'force_stopped' : 'stopped') : 'processing_files';
            
            $log->update([
                'status' => $preliminaryStatus,
                'total_mods' => $processedMods,
                'processed_mods' => $processedMods,
                'api_calls_made' => $apiCalls,
                'duration_seconds' => $currentDuration,
                'mods_per_second' => $currentModsPerSecond,
                'new_mods' => $newMods,
                'updated_mods' => $updatedMods,
                // Don't update completed_at yet as we might still be processing files
            ]);
            
            if ($wasStopped) {
                echo "\n📋 HARVEST STOPPED - PROCESSING FILES FOR COLLECTED MODS\n";
                if ($stopType === 'force') {
                    echo "⚡ Force stop detected - skipping file collection as requested.\n";
                } else {
                    echo "🔄 Graceful stop - proceeding to collect files for all discovered mods.\n";
                }
            } else {
                echo "\n🎉 CATEGORY HARVEST COMPLETE - PROCESSING FILES FOR ALL MODS\n";
                echo "🔄 All {$totalCategories} categories processed - now collecting complete file data.\n";
            }
            
            // Collect files unless it was a force stop
            if (!$wasStopped || $stopType !== 'force') {
                $uniqueModIds = array_unique($allModIds);
                $totalUniqueMods = count($uniqueModIds);
                
                echo "\n🎉 PHASE 1 COMPLETE - MOD COLLECTION SUMMARY:\n";
                echo "   📊 Categories processed: {$categoryCount}/{$totalCategories}\n";
                echo "   🔍 Total mods found: " . count($allModIds) . " (including duplicates)\n";
                echo "   🎯 Unique mods: {$totalUniqueMods}\n";
                echo "   📈 New/Updated: {$newMods}/{$updatedMods}\n\n";
                
                echo "📁 PHASE 2: Collecting Files for All Discovered Mods\n";
                echo "ℹ️  File collection has NO 10K limit - we get ALL files for each mod\n";
                echo "🔄 Processing {$totalUniqueMods} unique mods for complete file data...\n";
                echo "💡 Expected: 5-20 files per mod = " . ($totalUniqueMods * 8) . "+ total files\n\n";
                flush();
                
                $batchResult = $this->processBatchFiles($uniqueModIds, $apiCalls, $processedFiles, $newFiles, $updatedFiles, $log);
                $processedFiles = $batchResult['processed_files'];
                $newFiles = $batchResult['new_files'];
                $updatedFiles = $batchResult['updated_files'];
                $apiCalls = $batchResult['api_calls'];
            }

            // Final statistics
            $duration = now()->diffInSeconds($log->started_at);
            $modsPerSecond = $duration > 0 ? round($processedMods / $duration, 2) : 0;

            // Final database verification
            $finalModCount = Mod::count();
            $finalFileCount = ModFile::count();
            
            echo "\n🎉 Category Harvest Complete!\n";
            echo "📊 Final Stats:\n";
            echo "   • Categories Processed: {$categoryCount}/{$totalCategories}\n";
            echo "   • Session Processed: {$processedMods} mods\n";
            echo "   • Database Totals: {$finalModCount} mods, {$finalFileCount} files\n";
            echo "   • New/Updated: {$newMods}/{$updatedMods} mods, {$newFiles}/{$updatedFiles} files\n";
            echo "   • API Calls Made: {$apiCalls}\n";
            echo "   • Duration: {$duration}s\n";
            echo "   • Speed: {$modsPerSecond} mods/second\n";
            
            echo "\n✅ Category-based harvest successfully bypassed the 10,000 item limit!\n";
            echo "   🎯 Result: Accessed " . count($allModIds) . " unique mods across {$categoryCount} categories\n";
            echo "   📊 Theoretical max: " . ($totalCategories * 10000) . " mods ({$totalCategories} categories × 10K each)\n";
            echo "   🔥 This method can access ALL ~76,000 Minecraft mods!\n";

            // Determine final status
            $finalStatus = 'completed';
            if ($wasStopped) {
                $finalStatus = ($stopType === 'force') ? 'force_stopped' : 'stopped';
            }

            // Update log with final stats
            $log->update([
                'status' => $finalStatus,
                'total_mods' => $processedMods,
                'total_files' => $processedFiles,
                'processed_mods' => $processedMods,
                'processed_files' => $processedFiles,
                'api_calls_made' => $apiCalls,
                'completed_at' => now(),
                'duration_seconds' => $duration,
                'mods_per_second' => $modsPerSecond,
                'new_mods' => $newMods,
                'updated_mods' => $updatedMods,
                'new_files' => $newFiles,
                'updated_files' => $updatedFiles,
                'result_data' => json_encode([
                    'categories_processed' => $categoryCount,
                    'total_categories' => $totalCategories,
                    'new_mods' => $newMods,
                    'updated_mods' => $updatedMods,
                    'new_files' => $newFiles,
                    'updated_files' => $updatedFiles,
                    'mods_per_second' => $modsPerSecond,
                ])
            ]);

        } catch (\Exception $e) {
            echo "\n❌ Category harvest failed: " . $e->getMessage() . "\n";
            $duration = now()->diffInSeconds($log->started_at);
            $modsPerSecond = $duration > 0 && isset($processedMods) ? round($processedMods / $duration, 2) : 0;
            
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
                'duration_seconds' => $duration,
                'mods_per_second' => $modsPerSecond,
                'processed_mods' => $processedMods ?? 0,
                'processed_files' => $processedFiles ?? 0,
                'total_files' => $processedFiles ?? 0,
                'api_calls_made' => $apiCalls ?? 0,
                'new_mods' => $newMods ?? 0,
                'updated_mods' => $updatedMods ?? 0,
                'new_files' => $newFiles ?? 0,
                'updated_files' => $updatedFiles ?? 0,
            ]);
        }

        // Clean up cache keys
        if (isset($log)) {
            Cache::forget("harvest_stop_{$log->session_id}");
            Cache::forget("harvest_stop_type_{$log->session_id}");
        }

        flush();
    }

    /**
     * Clean up abandoned harvest sessions that are stuck in 'running' status
     */
    private function cleanupAbandonedSessions()
    {
        $abandonedThreshold = now()->subHours(2); // 2 hours ago
        
        $abandonedSessions = DirectHarvestLog::where('status', 'running')
            ->where('started_at', '<', $abandonedThreshold)
            ->get();
            
        foreach ($abandonedSessions as $session) {
            $duration = now()->diffInSeconds($session->started_at);
            $session->update([
                'status' => 'failed',
                'error_message' => 'Session abandoned or interrupted after ' . gmdate('H:i:s', $duration),
                'completed_at' => now(),
                'duration_seconds' => $duration,
            ]);
            
            // Clean up any related cache keys
            Cache::forget("harvest_stop_{$session->session_id}");
            Cache::forget("harvest_stop_type_{$session->session_id}");
        }
        
        if ($abandonedSessions->count() > 0) {
            echo "🧹 Cleaned up {$abandonedSessions->count()} abandoned harvest sessions\n";
            flush();
        }
    }
}