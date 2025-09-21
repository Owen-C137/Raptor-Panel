<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use PterodactylAddons\ShopSystem\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopSettings;

class SystemController extends Controller
{
    /**
     * Get system status
     */
    public function status()
    {
        $status = [
            'shop_enabled' => config('shop.enabled', false),
            'maintenance_mode' => ShopSettings::getValue('maintenance_mode', false),
            'total_orders' => \PterodactylAddons\ShopSystem\Models\ShopOrder::count(),
            'total_revenue' => \PterodactylAddons\ShopSystem\Models\ShopPayment::where('status', 'completed')->sum('amount'),
            'active_categories' => \PterodactylAddons\ShopSystem\Models\ShopCategory::where('active', true)->count(),
            'database_status' => $this->checkDatabaseStatus(),
            'cache_status' => $this->checkCacheStatus(),
            'queue_status' => $this->checkQueueStatus(),
        ];

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenance(Request $request)
    {
        $maintenanceMode = $request->boolean('enabled');
        
        ShopSettings::setValue('maintenance_mode', $maintenanceMode);
        
        $message = $maintenanceMode ? 'Maintenance mode enabled' : 'Maintenance mode disabled';
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'maintenance_mode' => $maintenanceMode
        ]);
    }

    /**
     * Clear system cache
     */
    public function clearCache()
    {
        try {
            // Clear various caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            // Clear shop-specific cache
            Cache::tags(['shop'])->flush();
            
            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check database connectivity
     */
    private function checkDatabaseStatus(): array
    {
        try {
            \DB::connection()->getPdo();
            return [
                'status' => 'connected',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check cache system
     */
    private function checkCacheStatus(): array
    {
        try {
            Cache::put('shop_system_test', 'working', 10);
            $test = Cache::get('shop_system_test');
            
            if ($test === 'working') {
                Cache::forget('shop_system_test');
                return [
                    'status' => 'working',
                    'message' => 'Cache system operational'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Cache system not working properly'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check queue system
     */
    private function checkQueueStatus(): array
    {
        try {
            // This is a basic check - in production you'd want more comprehensive monitoring
            $queueConnection = config('queue.default');
            
            return [
                'status' => 'configured',
                'message' => "Queue configured with {$queueConnection} driver",
                'connection' => $queueConnection
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue error: ' . $e->getMessage()
            ];
        }
    }
}
