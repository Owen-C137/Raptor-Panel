<?php

namespace App\Http\Middleware\Admin\Updates;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure only authorized administrators can access update system features.
 * 
 * This middleware provides granular permission checking for different update operations,
 * ensuring that users have appropriate permissions before accessing sensitive update
 * functionality.
 */
class UpdateSystemAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return redirect()->route('auth.login');
        }

        $user = Auth::user();

        // Ensure user is an admin
        if (!$user->root_admin) {
            abort(403, 'Access denied. Administrative privileges required.');
        }

        // Check specific permission if provided
        if ($permission && !$this->hasUpdatePermission($user, $permission)) {
            abort(403, "Access denied. Missing required permission: {$permission}");
        }

        // Check if update system is enabled
        if (!$this->isUpdateSystemEnabled()) {
            abort(503, 'Update system is currently disabled.');
        }

        // Log access for security auditing
        $this->logAccess($request, $user, $permission);

        return $next($request);
    }

    /**
     * Check if user has specific update system permission.
     *
     * @param  \App\Models\User  $user
     * @param  string  $permission
     * @return bool
     */
    private function hasUpdatePermission($user, string $permission): bool
    {
        // Define permission hierarchy
        $permissions = [
            'view' => 'View update status and history',
            'manage' => 'Initiate and manage updates',
            'configure' => 'Configure update system settings',
            'safety' => 'Access safety controls and emergency functions',
            'health' => 'View system health monitoring',
            'advanced' => 'Access advanced configuration options',
        ];

        // Root admins have all permissions
        if ($user->root_admin) {
            return true;
        }

        // Check specific permission (this would integrate with Pterodactyl's permission system)
        // For now, we'll use a simple check, but this should be extended based on actual needs
        return $user->hasPermission("admin.updates.{$permission}");
    }

    /**
     * Check if the update system is enabled.
     *
     * @return bool
     */
    private function isUpdateSystemEnabled(): bool
    {
        // Check system configuration
        $config = config('updates.enabled', true);
        
        // Check for maintenance mode override
        if (app()->isDownForMaintenance()) {
            return config('updates.allow_in_maintenance', false);
        }

        return $config;
    }

    /**
     * Log access to update system for security auditing.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  string|null  $permission
     * @return void
     */
    private function logAccess(Request $request, $user, ?string $permission): void
    {
        $logData = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route()->getName(),
            'method' => $request->method(),
            'permission' => $permission,
            'timestamp' => now(),
        ];

        // Log to application log
        logger('Update System Access', $logData);

        // Store in database for audit trail (if audit logging is enabled)
        if (config('updates.audit_logging', true)) {
            \App\Models\Updates\UpdateAuditLog::create([
                'user_id' => $user->id,
                'action' => 'access',
                'resource' => $request->route()->getName(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => json_encode($logData),
            ]);
        }
    }
}

/**
 * Middleware to check for active updates and prevent conflicting operations.
 */
class PreventConflictingOperations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for active updates
        $activeUpdate = \App\Services\Updates\UpdateStatusService::getActiveUpdate();
        
        // Define conflicting operations
        $conflictingRoutes = [
            'admin.updates.initiate',
            'admin.updates.schedule',
            'admin.updates.rollback.execute',
        ];

        // Check if current operation conflicts with active update
        if ($activeUpdate && in_array($request->route()->getName(), $conflictingRoutes)) {
            // Allow emergency actions even during updates
            if (str_contains($request->route()->getName(), 'emergency')) {
                return $next($request);
            }

            return response()->json([
                'success' => false,
                'error' => 'Cannot perform this operation while an update is active.',
                'active_update' => [
                    'session_id' => $activeUpdate->session_id,
                    'status' => $activeUpdate->status,
                    'started_at' => $activeUpdate->started_at,
                    'progress' => $activeUpdate->progress_percentage,
                ],
            ], 409);
        }

        return $next($request);
    }
}

/**
 * Middleware to validate system health before critical operations.
 */
class RequireHealthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check health for critical operations
        $criticalRoutes = [
            'admin.updates.initiate',
            'admin.updates.rollback.execute',
        ];

        if (!in_array($request->route()->getName(), $criticalRoutes)) {
            return $next($request);
        }

        // Skip health check if disabled in configuration
        if (!config('updates.require_health_check', true)) {
            return $next($request);
        }

        // Skip health check for emergency operations
        if ($request->input('emergency', false)) {
            return $next($request);
        }

        // Perform health check
        $healthService = app(\App\Services\Updates\HealthCheckService::class);
        $healthStatus = $healthService->performQuickHealthCheck();

        if (!$healthStatus['healthy']) {
            return response()->json([
                'success' => false,
                'error' => 'System health check failed. Operation cannot proceed safely.',
                'health_issues' => $healthStatus['issues'],
                'health_score' => $healthStatus['score'],
                'recommendations' => $healthStatus['recommendations'] ?? [],
            ], 422);
        }

        return $next($request);
    }
}

/**
 * Middleware to rate limit update operations to prevent abuse.
 */
class RateLimitUpdates
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $rateLimitKey = "update_operations:{$user->id}";
        
        // Define rate limits (per hour)
        $limits = [
            'admin.updates.initiate' => 5,  // Max 5 update attempts per hour
            'admin.updates.check' => 20,    // Max 20 update checks per hour
            'admin.updates.rollback.execute' => 3,  // Max 3 rollbacks per hour
        ];

        $route = $request->route()->getName();
        $limit = $limits[$route] ?? null;

        if ($limit) {
            $attempts = cache()->get($rateLimitKey, 0);
            
            if ($attempts >= $limit) {
                return response()->json([
                    'success' => false,
                    'error' => "Rate limit exceeded. Maximum {$limit} attempts per hour.",
                    'retry_after' => 3600 - (now()->minute * 60 + now()->second),
                ], 429);
            }

            // Increment attempt counter
            cache()->put($rateLimitKey, $attempts + 1, 3600);
        }

        return $next($request);
    }
}

/**
 * Middleware to ensure CSRF protection for state-changing operations.
 */
class UpdateCSRFProtection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // State-changing operations that require CSRF protection
        $stateChangingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        
        if (in_array($request->method(), $stateChangingMethods)) {
            // Verify CSRF token
            if (!$request->hasValidSignature() && !$request->session()->token() === $request->input('_token')) {
                // Special handling for AJAX requests
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'CSRF token mismatch. Please refresh the page and try again.',
                    ], 419);
                }

                abort(419, 'CSRF token mismatch');
            }
        }

        return $next($request);
    }
}

/**
 * Middleware to log all update operations for audit purposes.
 */
class AuditUpdateOperations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $user = Auth::user();

        // Process the request
        $response = $next($request);

        // Log the operation
        $this->logOperation($request, $response, $user, $startTime);

        return $response;
    }

    /**
     * Log the update operation for audit purposes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \App\Models\User  $user
     * @param  float  $startTime
     * @return void
     */
    private function logOperation($request, $response, $user, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // in milliseconds

        $logData = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => $request->route()->getName(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_data' => $this->sanitizeRequestData($request->all()),
            'response_status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'timestamp' => now(),
        ];

        // Log to file
        logger('Update Operation', $logData);

        // Store in database for audit trail
        if (config('updates.audit_logging', true)) {
            \App\Models\Updates\UpdateAuditLog::create([
                'user_id' => $user->id,
                'action' => $request->route()->getName(),
                'resource' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'response_status' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'metadata' => json_encode($logData),
            ]);
        }
    }

    /**
     * Sanitize request data to remove sensitive information.
     *
     * @param  array  $data
     * @return array
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitive = ['password', '_token', 'api_key', 'secret'];
        
        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }
}