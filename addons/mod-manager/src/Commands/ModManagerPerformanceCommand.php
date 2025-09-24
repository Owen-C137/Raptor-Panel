<?php

namespace PterodactylAddons\ModManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PterodactylAddons\ModManager\Services\CurseForgeApiService;

class ModManagerPerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod-manager:performance 
                            {action : show, optimize, benchmark, or monitor}
                            {--duration=60 : Monitor duration in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Performance monitoring and optimization for Mod Manager';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'show':
                $this->showPerformanceStats();
                break;
            case 'optimize':
                $this->optimizePerformance();
                break;
            case 'benchmark':
                $this->runBenchmark();
                break;
            case 'monitor':
                $this->monitorPerformance();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    /**
     * Show current performance statistics
     */
    private function showPerformanceStats(): void
    {
        $this->info('🚀 Mod Manager Performance Statistics');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Memory Usage
        $this->info('💾 Memory Usage:');
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        $this->line("  Current: {$this->formatBytes($memoryUsage)} ({$this->getMemoryPercentage($memoryUsage, $memoryLimit)}%)");
        $this->line("  Peak: {$this->formatBytes($peakMemory)} ({$this->getMemoryPercentage($peakMemory, $memoryLimit)}%)");
        $this->line("  Limit: {$this->formatBytes($memoryLimit)}");

        // Database Statistics
        $this->info('🗄️  Database Statistics:');
        $modCount = DB::table('mod_mods')->count();
        $fileCount = DB::table('mod_files')->count();
        $categoryCount = DB::table('mod_categories')->count();
        $installationCount = DB::table('mod_installations')->count();
        
        $this->line("  Mods: " . number_format($modCount));
        $this->line("  Files: " . number_format($fileCount));
        $this->line("  Categories: " . number_format($categoryCount));
        $this->line("  Installations: " . number_format($installationCount));

        // Cache Statistics
        $this->info('⚡ Cache Performance:');
        $cacheHits = Cache::get('mod-manager:cache:hits', 0);
        $cacheMisses = Cache::get('mod-manager:cache:misses', 0);
        $hitRate = $cacheHits + $cacheMisses > 0 ? 
            round(($cacheHits / ($cacheHits + $cacheMisses)) * 100, 1) : 0;
            
        $this->line("  Hit Rate: {$hitRate}% ({$cacheHits} hits, {$cacheMisses} misses)");

        // API Service Statistics
        $apiService = app(CurseForgeApiService::class);
        $apiStats = $apiService->getApiStats();
        
        $this->info('🌐 API Statistics:');
        $this->line("  Rate Limit: {$apiStats['rate_limit']} calls/sec");
        $this->line("  Tokens Available: {$apiStats['tokens']}");
        $this->line("  API Key: " . ($apiStats['api_key_configured'] ? '✅ Configured' : '❌ Missing'));
    }

    /**
     * Optimize performance settings
     */
    private function optimizePerformance(): void
    {
        $this->info('⚡ Optimizing Mod Manager Performance...');

        // Clear all caches
        $this->info('Clearing caches...');
        Cache::tags(['mod-manager'])->flush();
        
        // Optimize database
        $this->info('Optimizing database tables...');
        DB::statement('ANALYZE TABLE mod_mods, mod_files, mod_categories, mod_installations');
        
        // Reset cache counters
        Cache::put('mod-manager:cache:hits', 0);
        Cache::put('mod-manager:cache:misses', 0);
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            $collected = gc_collect_cycles();
            $this->info("Freed {$collected} memory cycles");
        }

        $this->info('✅ Performance optimization completed!');
    }

    /**
     * Run performance benchmark
     */
    private function runBenchmark(): void
    {
        $this->info('🏃 Running Performance Benchmark...');
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Test API service initialization
        $apiService = app(CurseForgeApiService::class);
        $apiTime = microtime(true) - $startTime;
        
        // Test database queries
        $dbStart = microtime(true);
        $mods = DB::table('mod_mods')->limit(100)->get();
        $dbTime = microtime(true) - $dbStart;
        
        // Test cache operations
        $cacheStart = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            Cache::put("benchmark:test:{$i}", "data_{$i}", 60);
            Cache::get("benchmark:test:{$i}");
        }
        $cacheTime = microtime(true) - $cacheStart;
        
        $totalTime = microtime(true) - $startTime;
        $memoryUsed = memory_get_usage(true) - $startMemory;
        
        $this->info('📊 Benchmark Results:');
        $this->line("  API Service Init: " . round($apiTime * 1000, 2) . "ms");
        $this->line("  Database Query (100 mods): " . round($dbTime * 1000, 2) . "ms");
        $this->line("  Cache Operations (100x): " . round($cacheTime * 1000, 2) . "ms");
        $this->line("  Total Time: " . round($totalTime * 1000, 2) . "ms");
        $this->line("  Memory Used: " . $this->formatBytes($memoryUsed));
        
        // Performance rating
        $rating = $this->calculatePerformanceRating($totalTime, $memoryUsed);
        $this->info("  Performance Rating: {$rating}");
        
        // Cleanup benchmark cache
        for ($i = 0; $i < 100; $i++) {
            Cache::forget("benchmark:test:{$i}");
        }
    }

    /**
     * Monitor performance in real-time
     */
    private function monitorPerformance(): void
    {
        $duration = (int) $this->option('duration');
        $this->info("🔍 Monitoring performance for {$duration} seconds...");
        
        $startTime = time();
        $measurements = [];
        
        while (time() - $startTime < $duration) {
            $measurement = [
                'timestamp' => time(),
                'memory' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'load_average' => sys_getloadavg()[0] ?? 0,
            ];
            
            $measurements[] = $measurement;
            
            // Show live stats every 5 seconds
            if (count($measurements) % 5 === 0) {
                $this->line("[" . date('H:i:s') . "] Memory: " . 
                    $this->formatBytes($measurement['memory']) . 
                    " | Load: " . round($measurement['load_average'], 2));
            }
            
            sleep(1);
        }
        
        // Show summary
        $avgMemory = array_sum(array_column($measurements, 'memory')) / count($measurements);
        $maxMemory = max(array_column($measurements, 'memory'));
        $avgLoad = array_sum(array_column($measurements, 'load_average')) / count($measurements);
        
        $this->info('📈 Monitoring Summary:');
        $this->line("  Average Memory: " . $this->formatBytes($avgMemory));
        $this->line("  Peak Memory: " . $this->formatBytes($maxMemory));
        $this->line("  Average Load: " . round($avgLoad, 2));
    }

    /**
     * Calculate performance rating
     */
    private function calculatePerformanceRating(float $totalTime, int $memoryUsed): string
    {
        $timeScore = $totalTime < 0.1 ? 10 : ($totalTime < 0.5 ? 8 : ($totalTime < 1.0 ? 6 : 4));
        $memoryScore = $memoryUsed < 1024*1024 ? 10 : ($memoryUsed < 5*1024*1024 ? 8 : 6);
        
        $averageScore = ($timeScore + $memoryScore) / 2;
        
        if ($averageScore >= 9) return '🚀 Excellent';
        if ($averageScore >= 7) return '✅ Good';
        if ($averageScore >= 5) return '⚠️  Fair';
        return '❌ Poor';
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
     * Get memory usage percentage
     */
    private function getMemoryPercentage(int $current, int $limit): string
    {
        if ($limit === 0) return '0';
        return number_format(($current / $limit) * 100, 1);
    }
}