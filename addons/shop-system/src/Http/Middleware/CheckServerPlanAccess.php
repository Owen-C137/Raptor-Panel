<?php

namespace PterodactylAddons\ShopSystem\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use Pterodactyl\Models\Server;

class CheckServerPlanAccess
{
    /**
     * Handle an incoming request to ensure user has active plan for server access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply to server routes
        $serverId = $request->route('server');
        if (!$serverId) {
            return $next($request);
        }

        // Get the server model
        $server = Server::where('uuidShort', $serverId)
            ->orWhere('uuid', $serverId)
            ->first();

        if (!$server) {
            return $next($request);
        }

        // Skip check for server owners - they always have access
        if ($server->owner_id === Auth::id()) {
            return $next($request);
        }

        // Check if user has an active plan for this server
        $activePlan = ShopOrder::where('server_id', $server->id)
            ->where('user_id', Auth::id())
            ->where('status', ShopOrder::STATUS_ACTIVE)
            ->exists();

        if (!$activePlan) {
            // User doesn't have an active plan, deny access
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'You do not have an active plan for this server.'
                ], 403);
            }

            // Redirect to server plan purchase page
            return redirect()->route('shop.index')
                ->with('error', 'You need an active plan to access this server.');
        }

        return $next($request);
    }
}