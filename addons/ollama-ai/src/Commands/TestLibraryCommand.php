<?php

namespace PterodactylAddons\OllamaAi\Commands;

use Illuminate\Console\Command;
use PterodactylAddons\OllamaAi\Services\OllamaService;

class TestLibraryCommand extends Command
{
    protected $signature = 'ollama:test-library {--refresh : Force refresh cache}';
    protected $description = 'Test the model library functionality through OllamaService';

    public function handle()
    {
        $this->info('Testing Ollama library through OllamaService...');
        
        $ollamaService = app(OllamaService::class);
        
        try {
            $forceRefresh = $this->option('refresh');
            
            $this->info('Fetching enhanced models from OllamaService...');
            $models = $ollamaService->getOllamaLibraryModels($forceRefresh);
            
            $this->info("Found " . count($models) . " enhanced models:");
            
            foreach (array_slice($models, 0, 10) as $model) { // Show first 10
                $this->line("- {$model['title']} ({$model['name']}) - {$model['downloads']} downloads - {$model['size']}");
            }
            
            if (count($models) > 10) {
                $this->line("... and " . (count($models) - 10) . " more models");
            }
            
            if (count($models) === 0) {
                $this->warn('No models returned! Checking fallback...');
                return 1;
            }
            
            $this->info('✅ Library test completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('❌ Library test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}