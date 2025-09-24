<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;

class BaseController extends Controller
{
    /**
     * BaseController constructor.
     */
    public function __construct(private ViewFactory $view)
    {
    }

    /**
     * Return the admin index view.
     */
    public function index(): View
    {
        // Auto-clear version-related caches for immediate visibility of changes
        $this->clearVersionCaches();
        
        // Get statistics for dashboard
        $servers = Server::count();
        $suspensions = Server::where('status', 'suspended')->count();
        $nodes = Node::count();
        $users = User::count();
        
        $versionService = app(\Pterodactyl\Services\VersionService::class);
        
        return $this->view->make('admin.index', [
            'servers' => $servers,
            'suspensions' => $suspensions,
            'nodes' => $nodes,
            'users' => $users,
            'appVersion' => $versionService->getCurrentVersion(),
        ]);
    }
    
    /**
     * Clear caches that might prevent version updates from showing immediately
     */
    private function clearVersionCaches(): void
    {
        // Clear update check cache so version changes are immediately visible
        Cache::forget('raptor_panel_update_check');
        
        // Clear config cache if it exists (in case version was manually updated)
        if (Cache::has('config')) {
            try {
                \Artisan::call('config:clear');
                \Artisan::call('config:cache');
            } catch (\Exception $e) {
                // Silently fail if unable to clear config cache
            }
        }
    }
}
