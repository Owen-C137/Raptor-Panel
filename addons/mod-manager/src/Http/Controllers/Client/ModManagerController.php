<?php

namespace PterodactylAddons\ModManager\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use PterodactylAddons\ModManager\Models\Mod;
use PterodactylAddons\ModManager\Models\ModFile;
use PterodactylAddons\ModManager\Models\ModInstallation;
use PterodactylAddons\ModManager\Services\CurseForgeApiService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class ModManagerController extends ClientApiController
{
    public function __construct(
        private CurseForgeApiService $curseForgeService,
        private DaemonFileRepository $fileRepository
    ) {
        parent::__construct();
    }

    /**
     * Get installed mods from the server's mods/ directory
     */
    public function getInstalledMods(ClientApiRequest $request, Server $server): JsonResponse
    {
        try {
            // Load the server with node relationship if not already loaded
            if (!$server->relationLoaded('node')) {
                $server->load('node');
            }

            Log::info('ModManager: getInstalledMods called', [
                'server_uuid' => $server->uuid,
                'server_id' => $server->id,
                'server_name' => $server->name,
                'node_id' => $server->node->id ?? 'NULL',
                'node_name' => $server->node->name ?? 'NULL',
                'user_id' => $request->user()->id,
            ]);

            // Try multiple possible paths for the mods directory
            $possiblePaths = ['/mods', 'mods', './mods'];
            $installedMods = [];
            $successfulPath = null;
            $pathResults = [];

            Log::info('ModManager: Fetching installed mods', [
                'server_uuid' => $server->uuid,
                'attempting_paths' => $possiblePaths
            ]);

            foreach ($possiblePaths as $modsPath) {
                try {
                    Log::debug("ModManager: Trying mods path: {$modsPath}");
                    
                    // Get all files from the mods directory
                    $files = $this->fileRepository
                        ->setServer($server)
                        ->getDirectory($modsPath);
                    
                    $successfulPath = $modsPath;
                    $pathResults[$modsPath] = 'SUCCESS - ' . count($files) . ' items found';
                    
                    Log::info('ModManager: Successfully accessed mods directory', [
                        'server_uuid' => $server->uuid,
                        'path' => $modsPath,
                        'file_count' => count($files),
                        'files_sample' => collect($files)->take(5)->map(fn($f) => [
                            'name' => $f['name'] ?? 'unknown', 
                            'is_file' => $f['file'] ?? true, 
                            'size' => $f['size'] ?? 0,
                            'extension' => pathinfo($f['name'] ?? '', PATHINFO_EXTENSION)
                        ])->toArray()
                    ]);

                    foreach ($files as $file) {
                        // The daemon returns files as arrays, not objects
                        // Each file is an array with keys: name, file, size, etc.
                        $fileName = $file['name'] ?? '';
                        $isFile = $file['file'] ?? true;
                        $fileSize = $file['size'] ?? 0;
                        $fileModified = $file['modified'] ?? null;
                        
                        // Skip directories
                        if (!$isFile) {
                            Log::debug("ModManager: Skipping directory: {$fileName}");
                            continue;
                        }
                        
                        // Only process .jar files (mods)
                        if (pathinfo($fileName, PATHINFO_EXTENSION) === 'jar') {
                            $modData = [
                                'name' => $this->getModNameFromFileName($fileName),
                                'fileName' => $fileName,
                                'size' => $fileSize,
                                'lastModified' => $fileModified ? now()->parse($fileModified)->toISOString() : now()->toISOString(),
                                'version' => $this->extractVersionFromFileName($fileName),
                            ];
                            $installedMods[] = $modData;
                            Log::debug('ModManager: Added mod to installed list', $modData);
                        }
                    }
                    
                    // If we got here without error, we found the right path
                    break;
                    
                } catch (Exception $pathException) {
                    $pathResults[$modsPath] = 'ERROR - ' . $pathException->getMessage();
                    Log::debug('ModManager: Failed to access path', [
                        'path' => $modsPath,
                        'error' => $pathException->getMessage()
                    ]);
                    // Continue trying other paths
                    continue;
                }
            }

            if ($successfulPath === null) {
                Log::warning('ModManager: Could not access any mods directory path', [
                    'server_uuid' => $server->uuid,
                    'attempted_paths' => $pathResults
                ]);
                
                // Return debug info
                return response()->json([
                    'debug' => true,
                    'message' => 'Could not access mods directory',
                    'attempted_paths' => $pathResults,
                    'server_info' => [
                        'uuid' => $server->uuid,
                        'id' => $server->id,
                        'name' => $server->name,
                        'node' => $server->node ? $server->node->name : 'NULL'
                    ]
                ]);
            }

            Log::info('ModManager: Completed installed mods fetch', [
                'server_uuid' => $server->uuid,
                'successful_path' => $successfulPath,
                'installed_count' => count($installedMods)
            ]);

            return response()->json($installedMods);
            
        } catch (Exception $e) {
            Log::error('ModManager: Failed to load installed mods', [
                'server_uuid' => $server->uuid ?? 'NULL',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'debug' => true,
                'error' => true,
                'message' => 'Failed to load installed mods: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get available mods from the database
     */
    public function getAvailableMods(ClientApiRequest $request, Server $server): JsonResponse
    {
        try {
            $mods = Mod::with(['latestFile'])
                ->where('game_id', 432) // Minecraft game ID
                ->orderBy('download_count', 'desc')
                ->limit(100) // Limit to top 100 mods for performance
                ->get();

            $availableMods = $mods->map(function ($mod) {
                return [
                    'id' => $mod->id,
                    'name' => $mod->name,
                    'slug' => $mod->slug,
                    'summary' => $mod->summary,
                    'download_count' => $mod->download_count,
                    'logo_url' => $mod->logo_url,
                    'categories' => json_decode($mod->categories, true) ?? [],
                    'latest_version' => $mod->latestFile ? [
                        'id' => $mod->latestFile->id,
                        'display_name' => $mod->latestFile->display_name,
                        'file_name' => $mod->latestFile->file_name,
                        'download_url' => $mod->latestFile->download_url,
                        'game_versions' => json_decode($mod->latestFile->game_versions, true) ?? [],
                        'release_type' => $mod->latestFile->release_type,
                    ] : null,
                ];
            });

            return response()->json($availableMods);
        } catch (Exception $e) {
            Log::error('Failed to load available mods', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to load available mods'
            ], 500);
        }
    }

    /**
     * Install a mod to the server
     */
    public function installMod(ClientApiRequest $request, Server $server): JsonResponse
    {
        $request->validate([
            'mod_id' => 'required|integer|exists:mod_mods,id',
            'file_id' => 'required|integer|exists:mod_files,id',
        ]);

        try {
            $mod = Mod::findOrFail($request->input('mod_id'));
            $file = ModFile::findOrFail($request->input('file_id'));

            // Ensure the file belongs to the mod
            if ($file->mod_id !== $mod->id) {
                return response()->json([
                    'message' => 'File does not belong to the specified mod'
                ], 400);
            }

            // Check if mod is already installed
            $existingInstallation = ModInstallation::where('server_id', $server->id)
                ->where('mod_id', $mod->id)
                ->first();

            if ($existingInstallation) {
                return response()->json([
                    'message' => 'Mod is already installed'
                ], 400);
            }

            // Download the mod file
            $downloadUrl = $file->download_url;
            if (!$downloadUrl) {
                // If no direct download URL, construct CurseForge download URL
                $downloadUrl = "https://edge.forgecdn.net/files/{$file->curse_file_id}/{$file->file_name}";
            }

            // Download the file
            $client = new Client();
            $response = $client->get($downloadUrl);
            $modContent = $response->getBody()->getContents();

            // Save to server's mods directory using daemon file repository
            $modsPath = '/mods/' . $file->file_name;
            $this->fileRepository
                ->setServer($server)
                ->putContent($modsPath, $modContent);

            // Create installation record
            ModInstallation::create([
                'user_id' => $request->user()->id,
                'server_id' => $server->id,
                'mod_id' => $mod->id,
                'file_id' => $file->id,
                'installation_path' => $modsPath,
                'status' => 'installed',
                'installed_version' => $file->display_name,
                'is_enabled' => true,
                'installed_at' => now(),
            ]);

            Log::info('Mod installed successfully', [
                'server_uuid' => $server->uuid,
                'mod_name' => $mod->name,
                'file_name' => $file->file_name,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Mod installed successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to install mod', [
                'server_uuid' => $server->uuid,
                'mod_id' => $request->input('mod_id'),
                'file_id' => $request->input('file_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to install mod: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Uninstall a mod from the server
     */
    public function uninstallMod(ClientApiRequest $request, Server $server): JsonResponse
    {
        $request->validate([
            'file_name' => 'required|string',
        ]);

        try {
            $fileName = $request->input('file_name');
            $filePath = '/mods/' . $fileName;

            // Delete the file from server using daemon file repository
            $this->fileRepository
                ->setServer($server)
                ->deleteFiles('/', [$filePath]);

            // Remove installation record if it exists
            ModInstallation::where('server_id', $server->id)
                ->where('installation_path', $filePath)
                ->delete();

            Log::info('Mod uninstalled successfully', [
                'server_uuid' => $server->uuid,
                'file_name' => $fileName,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Mod uninstalled successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to uninstall mod', [
                'server_uuid' => $server->uuid,
                'file_name' => $request->input('file_name'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to uninstall mod: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract mod name from filename
     */
    private function getModNameFromFileName(string $fileName): string
    {
        // Remove .jar extension
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        
        // Remove version numbers and common patterns
        $name = preg_replace('/[-_]?(\d+\.)+\d+/', '', $name);
        $name = preg_replace('/[-_]?(mc|minecraft|forge|fabric)[-_]?(\d+\.)+\d+/i', '', $name);
        $name = preg_replace('/[-_]+/', ' ', $name);
        
        return trim($name) ?: $fileName;
    }

    /**
     * Extract version from filename
     */
    private function extractVersionFromFileName(string $fileName): ?string
    {
        // Try to find version patterns
        if (preg_match('/(\d+\.)+\d+/', $fileName, $matches)) {
            return $matches[0];
        }
        
        return null;
    }
}