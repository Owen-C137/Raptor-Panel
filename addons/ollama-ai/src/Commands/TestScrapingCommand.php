<?php

namespace PterodactylAddons\OllamaAi\Commands;

use Illuminate\Console\Command;
use PterodactylAddons\OllamaAi\Services\OllamaLibraryScraperService;

class TestScrapingCommand extends Command
{
    protected $signature = 'ollama:test-scraping {--refresh : Force refresh cache}';
    protected $description = 'Test the Ollama library scraping functionality';

    public function handle()
    {
        $this->info('Testing Ollama library scraping...');
        
        $scraperService = new OllamaLibraryScraperService();
        
        try {
            $forceRefresh = $this->option('refresh');
            
            $this->info('Fetching models from Ollama library...');
            $models = $scraperService->getAllModels($forceRefresh);
            
            $this->info("Found " . count($models) . " models:");
            
            foreach (array_slice($models, 0, 5) as $model) { // Show first 5
                $this->line("- {$model['title']} ({$model['slug']}) - {$model['downloads']} downloads");
            }
            
            if (count($models) > 5) {
                $this->line("... and " . (count($models) - 5) . " more models");
            }
            
            // Test getting details for one popular model and show raw HTML sample
            if (!empty($models)) {
                $topModel = $models[0];
                $this->info("Getting details for: " . $topModel['slug']);
                
                $details = $scraperService->getModelDetails($topModel['slug']);
                if ($details) {
                    $this->info("Model details retrieved successfully!");
                    $this->line("Title: '" . $details['title'] . "'");
                    $this->line("Description: " . substr($details['description'], 0, 100) . '...');
                    $this->line("Variants: " . count($details['variants']));
                    
                    if (!empty($details['variants'])) {
                        $this->line("First variant: " . $details['variants'][0]['name'] . " (" . $details['variants'][0]['size'] . ")");
                        if (count($details['variants']) > 1) {
                            $this->line("All variants:");
                            foreach ($details['variants'] as $variant) {
                                $this->line("  - " . $variant['name'] . " (" . $variant['size'] . ", " . $variant['context'] . " context)");
                            }
                        }
                    }
                } else {
                    $this->warn("Could not get details for " . $topModel['slug']);
                }
            }
            
            $this->info('✅ Scraping test completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('❌ Scraping test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}