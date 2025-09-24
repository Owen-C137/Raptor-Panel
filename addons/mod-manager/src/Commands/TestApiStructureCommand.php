<?php

namespace PterodactylAddons\ModManager\Commands;

use Illuminate\Console\Command;
use PterodactylAddons\ModManager\Services\CurseForgeApiService;
use PterodactylAddons\ModManager\Models\Game;

class TestApiStructureCommand extends Command
{
    protected $signature = 'mod-manager:test-api-structure';
    protected $description = 'Test CurseForge API response structure to debug missing fields';

    private CurseForgeApiService $apiService;

    public function __construct(CurseForgeApiService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
    }

    public function handle()
    {
        $this->info('🔍 Testing CurseForge API Structure...');
        
        // Test mod response
        $this->info('📋 Testing MOD response structure...');
        $response = $this->apiService->searchMods([
            'gameId' => 432,
            'sortField' => 2,
            'sortOrder' => 'desc',
            'pageSize' => 1
        ]);

        if ($response && isset($response['data'][0])) {
            $mod = $response['data'][0];
            
            $this->info("Mod ID: {$mod['id']}");
            $this->info("Mod Name: {$mod['name']}");
            
            $this->info("\n📋 Available mod fields:");
            $this->line(implode(', ', array_keys($mod)));
            
            // Check authors
            $this->info("\n👥 AUTHORS:");
            if (isset($mod['authors'])) {
                $this->info("✅ Found authors field - type: " . gettype($mod['authors']));
                $this->info("   Count: " . count($mod['authors']));
                if (!empty($mod['authors'])) {
                    $this->info("   First author: " . json_encode($mod['authors'][0], JSON_PRETTY_PRINT));
                }
            } else {
                $this->error("❌ No authors field in API response");
            }
            
            // Check categories
            $this->info("\n📂 CATEGORIES:");
            if (isset($mod['categories'])) {
                $this->info("✅ Found categories field - type: " . gettype($mod['categories']));
                $this->info("   Count: " . count($mod['categories']));
                if (!empty($mod['categories'])) {
                    $this->info("   First category: " . json_encode($mod['categories'][0], JSON_PRETTY_PRINT));
                }
            } else {
                $this->error("❌ No categories field in API response");
            }
            
            // Test file response
            $this->info("\n📁 Testing FILE response structure...");
            $fileResponse = $this->apiService->getModFiles($mod['id']);
            
            if ($fileResponse && isset($fileResponse['data'][0])) {
                $file = $fileResponse['data'][0];
                
                $this->info("File ID: {$file['id']}");
                $this->info("File Name: {$file['displayName']}");
                
                $this->info("\n📁 Available file fields:");
                $this->line(implode(', ', array_keys($file)));
                
                // Check download count
                $this->info("\n📊 DOWNLOAD COUNT:");
                if (isset($file['downloadCount'])) {
                    $this->info("✅ Found downloadCount field: " . $file['downloadCount']);
                } else {
                    $this->error("❌ No downloadCount field in file API response");
                }
                
            } else {
                $this->error("❌ Failed to get file data");
            }
            
        } else {
            $this->error("❌ Failed to get mod data");
        }
    }
}