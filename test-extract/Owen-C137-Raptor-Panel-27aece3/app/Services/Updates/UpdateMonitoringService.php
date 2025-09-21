<?php

namespace Pterodactyl\Services\Updates;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Pterodactyl\Models\UpdateSession;
use Pterodactyl\Models\UpdateStep;
use Pterodactyl\Events\UpdateProgressEvent;
use Pterodactyl\Events\UpdateStatusChangedEvent;
use Pterodactyl\Events\UpdateErrorEvent;
use Pterodactyl\Events\UpdateCompletedEvent;

/**
 * Real-time Update Monitoring Service
 * 
 * Provides real-time monitoring of update progress using WebSockets,
 * broadcasting live updates to connected clients, and managing
 * update status notifications.
 */
class UpdateMonitoringService
{
    /**
     * @var string
     */
    private const CACHE_PREFIX = 'update_monitoring:';
    
    /**
     * @var int
     */
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * @var array
     */
    private array $connectedClients = [];

    /**
     * @var array
     */
    private array $subscriptions = [];

    /**
     * Initialize monitoring service.
     */
    public function __construct()
    {
        $this->setupEventListeners();
    }

    /**
     * Setup event listeners for update events.
     */
    private function setupEventListeners(): void
    {
        Event::listen(UpdateProgressEvent::class, [$this, 'handleProgressUpdate']);
        Event::listen(UpdateStatusChangedEvent::class, [$this, 'handleStatusChange']);
        Event::listen(UpdateErrorEvent::class, [$this, 'handleUpdateError']);
        Event::listen(UpdateCompletedEvent::class, [$this, 'handleUpdateCompleted']);
    }

    /**
     * Start monitoring an update session.
     */
    public function startMonitoring(string $sessionId): array
    {
        try {
            $session = UpdateSession::findOrFail($sessionId);
            
            // Initialize monitoring data
            $monitoringData = [
                'session_id' => $sessionId,
                'status' => $session->status,
                'progress' => [
                    'percentage' => $session->progress_percentage ?? 0,
                    'current_step' => $session->current_step ?? 'Initializing',
                    'total_steps' => $session->total_steps ?? 0,
                    'completed_steps' => $session->completed_steps ?? 0,
                ],
                'timing' => [
                    'started_at' => $session->started_at,
                    'estimated_completion' => $this->calculateEstimatedCompletion($session),
                    'duration' => $this->calculateDuration($session),
                ],
                'real_time_data' => [
                    'cpu_usage' => $this->getCurrentCpuUsage(),
                    'memory_usage' => $this->getCurrentMemoryUsage(),
                    'disk_io' => $this->getCurrentDiskIo(),
                ],
                'connected_clients' => count($this->connectedClients),
                'last_update' => now(),
            ];

            // Cache monitoring data
            Cache::put(
                self::CACHE_PREFIX . "session:{$sessionId}",
                $monitoringData,
                self::CACHE_TTL
            );

            // Broadcast monitoring started
            $this->broadcastToClients('monitoring.started', $monitoringData);

            Log::info("Started monitoring update session: {$sessionId}");

            return $monitoringData;
        } catch (Exception $e) {
            Log::error("Failed to start monitoring session {$sessionId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Stop monitoring an update session.
     */
    public function stopMonitoring(string $sessionId): bool
    {
        try {
            // Remove from cache
            Cache::forget(self::CACHE_PREFIX . "session:{$sessionId}");

            // Broadcast monitoring stopped
            $this->broadcastToClients('monitoring.stopped', [
                'session_id' => $sessionId,
                'stopped_at' => now(),
            ]);

            Log::info("Stopped monitoring update session: {$sessionId}");

            return true;
        } catch (Exception $e) {
            Log::error("Failed to stop monitoring session {$sessionId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get real-time monitoring data for a session.
     */
    public function getMonitoringData(string $sessionId): ?array
    {
        try {
            $cachedData = Cache::get(self::CACHE_PREFIX . "session:{$sessionId}");
            
            if (!$cachedData) {
                return null;
            }

            // Update real-time metrics
            $cachedData['real_time_data'] = [
                'cpu_usage' => $this->getCurrentCpuUsage(),
                'memory_usage' => $this->getCurrentMemoryUsage(),
                'disk_io' => $this->getCurrentDiskIo(),
                'network_io' => $this->getCurrentNetworkIo(),
            ];

            $cachedData['last_update'] = now();
            $cachedData['connected_clients'] = count($this->connectedClients);

            // Update cache
            Cache::put(
                self::CACHE_PREFIX . "session:{$sessionId}",
                $cachedData,
                self::CACHE_TTL
            );

            return $cachedData;
        } catch (Exception $e) {
            Log::error("Failed to get monitoring data for session {$sessionId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all active monitoring sessions.
     */
    public function getActiveSessions(): array
    {
        try {
            $activeSessions = [];
            $pattern = self::CACHE_PREFIX . "session:*";
            
            $keys = Cache::getRedis()->keys($pattern);
            
            foreach ($keys as $key) {
                $sessionId = str_replace(self::CACHE_PREFIX . "session:", '', $key);
                $data = Cache::get($key);
                
                if ($data) {
                    $activeSessions[$sessionId] = $data;
                }
            }

            return $activeSessions;
        } catch (Exception $e) {
            Log::error("Failed to get active monitoring sessions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Subscribe client to update notifications.
     */
    public function subscribeClient(string $clientId, array $channels = ['all']): bool
    {
        try {
            $this->connectedClients[$clientId] = [
                'connected_at' => now(),
                'channels' => $channels,
                'last_ping' => now(),
            ];

            $this->subscriptions[$clientId] = $channels;

            Log::info("Client {$clientId} subscribed to channels: " . implode(', ', $channels));

            return true;
        } catch (Exception $e) {
            Log::error("Failed to subscribe client {$clientId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unsubscribe client from notifications.
     */
    public function unsubscribeClient(string $clientId): bool
    {
        try {
            unset($this->connectedClients[$clientId]);
            unset($this->subscriptions[$clientId]);

            Log::info("Client {$clientId} unsubscribed");

            return true;
        } catch (Exception $e) {
            Log::error("Failed to unsubscribe client {$clientId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle update progress event.
     */
    public function handleProgressUpdate(UpdateProgressEvent $event): void
    {
        try {
            $sessionId = $event->sessionId;
            $progressData = $event->progressData;

            // Update cached monitoring data
            $monitoringData = $this->getMonitoringData($sessionId);
            
            if ($monitoringData) {
                $monitoringData['progress'] = array_merge(
                    $monitoringData['progress'],
                    $progressData
                );
                
                $monitoringData['timing']['duration'] = $this->calculateDuration(
                    UpdateSession::find($sessionId)
                );

                Cache::put(
                    self::CACHE_PREFIX . "session:{$sessionId}",
                    $monitoringData,
                    self::CACHE_TTL
                );
            }

            // Broadcast progress update
            $this->broadcastToClients('progress.updated', [
                'session_id' => $sessionId,
                'progress' => $progressData,
                'timestamp' => now(),
            ]);

            Log::debug("Progress update for session {$sessionId}: " . json_encode($progressData));
        } catch (Exception $e) {
            Log::error("Failed to handle progress update: " . $e->getMessage());
        }
    }

    /**
     * Handle update status change event.
     */
    public function handleStatusChange(UpdateStatusChangedEvent $event): void
    {
        try {
            $sessionId = $event->sessionId;
            $newStatus = $event->newStatus;
            $oldStatus = $event->oldStatus;

            // Update cached monitoring data
            $monitoringData = $this->getMonitoringData($sessionId);
            
            if ($monitoringData) {
                $monitoringData['status'] = $newStatus;
                
                Cache::put(
                    self::CACHE_PREFIX . "session:{$sessionId}",
                    $monitoringData,
                    self::CACHE_TTL
                );
            }

            // Broadcast status change
            $this->broadcastToClients('status.changed', [
                'session_id' => $sessionId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'timestamp' => now(),
            ]);

            Log::info("Status changed for session {$sessionId}: {$oldStatus} -> {$newStatus}");
        } catch (Exception $e) {
            Log::error("Failed to handle status change: " . $e->getMessage());
        }
    }

    /**
     * Handle update error event.
     */
    public function handleUpdateError(UpdateErrorEvent $event): void
    {
        try {
            $sessionId = $event->sessionId;
            $error = $event->error;

            // Broadcast error notification
            $this->broadcastToClients('error.occurred', [
                'session_id' => $sessionId,
                'error' => [
                    'message' => $error['message'] ?? 'Unknown error',
                    'code' => $error['code'] ?? null,
                    'step' => $error['step'] ?? null,
                    'severity' => $error['severity'] ?? 'error',
                ],
                'timestamp' => now(),
            ]);

            Log::error("Error in update session {$sessionId}: " . json_encode($error));
        } catch (Exception $e) {
            Log::error("Failed to handle update error: " . $e->getMessage());
        }
    }

    /**
     * Handle update completed event.
     */
    public function handleUpdateCompleted(UpdateCompletedEvent $event): void
    {
        try {
            $sessionId = $event->sessionId;
            $result = $event->result;

            // Broadcast completion notification
            $this->broadcastToClients('update.completed', [
                'session_id' => $sessionId,
                'result' => $result,
                'timestamp' => now(),
            ]);

            // Stop monitoring after a delay
            dispatch(function () use ($sessionId) {
                sleep(60); // Keep monitoring for 1 minute after completion
                $this->stopMonitoring($sessionId);
            })->delay(now()->addMinute());

            Log::info("Update completed for session {$sessionId}: " . json_encode($result));
        } catch (Exception $e) {
            Log::error("Failed to handle update completion: " . $e->getMessage());
        }
    }

    /**
     * Broadcast message to subscribed clients.
     */
    public function broadcastToClients(string $event, array $data): void
    {
        try {
            $message = [
                'event' => $event,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ];

            foreach ($this->connectedClients as $clientId => $clientData) {
                // Check if client is subscribed to this event type
                if ($this->isClientSubscribedToEvent($clientId, $event)) {
                    $this->sendMessageToClient($clientId, $message);
                }
            }

            // Also broadcast via Laravel Broadcasting
            broadcast(new UpdateProgressEvent($data['session_id'] ?? 'system', $data));
        } catch (Exception $e) {
            Log::error("Failed to broadcast to clients: " . $e->getMessage());
        }
    }

    /**
     * Send message to specific client.
     */
    private function sendMessageToClient(string $clientId, array $message): void
    {
        try {
            // Implementation depends on WebSocket server being used
            // This is a placeholder for the actual WebSocket implementation
            
            Log::debug("Sending message to client {$clientId}: " . json_encode($message));
        } catch (Exception $e) {
            Log::error("Failed to send message to client {$clientId}: " . $e->getMessage());
        }
    }

    /**
     * Check if client is subscribed to event type.
     */
    private function isClientSubscribedToEvent(string $clientId, string $event): bool
    {
        $channels = $this->subscriptions[$clientId] ?? [];
        
        // Client subscribed to all events
        if (in_array('all', $channels)) {
            return true;
        }

        // Check specific event subscriptions
        $eventCategory = explode('.', $event)[0];
        
        return in_array($eventCategory, $channels) || in_array($event, $channels);
    }

    /**
     * Get real-time system metrics.
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'system' => [
                'cpu_usage' => $this->getCurrentCpuUsage(),
                'memory_usage' => $this->getCurrentMemoryUsage(),
                'disk_io' => $this->getCurrentDiskIo(),
                'network_io' => $this->getCurrentNetworkIo(),
                'load_average' => $this->getLoadAverage(),
            ],
            'update_system' => [
                'active_sessions' => count($this->getActiveSessions()),
                'connected_clients' => count($this->connectedClients),
                'queue_size' => $this->getUpdateQueueSize(),
                'cache_usage' => $this->getCacheUsage(),
            ],
            'timestamp' => now(),
        ];
    }

    /**
     * Get monitoring statistics.
     */
    public function getMonitoringStats(): array
    {
        return [
            'active_sessions' => count($this->getActiveSessions()),
            'connected_clients' => count($this->connectedClients),
            'total_subscriptions' => count($this->subscriptions),
            'cache_keys' => $this->getCacheKeyCount(),
            'uptime' => $this->getMonitoringUptime(),
        ];
    }

    /**
     * Clean up stale monitoring data.
     */
    public function cleanup(): int
    {
        try {
            $cleaned = 0;
            $pattern = self::CACHE_PREFIX . "*";
            
            $keys = Cache::getRedis()->keys($pattern);
            
            foreach ($keys as $key) {
                $data = Cache::get($key);
                
                if ($data && isset($data['last_update'])) {
                    $lastUpdate = carbon($data['last_update']);
                    
                    // Remove data older than 2 hours
                    if ($lastUpdate->diffInHours(now()) > 2) {
                        Cache::forget($key);
                        $cleaned++;
                    }
                }
            }

            // Clean up disconnected clients
            foreach ($this->connectedClients as $clientId => $clientData) {
                $lastPing = carbon($clientData['last_ping']);
                
                if ($lastPing->diffInMinutes(now()) > 5) {
                    $this->unsubscribeClient($clientId);
                    $cleaned++;
                }
            }

            Log::info("Cleaned up {$cleaned} stale monitoring entries");

            return $cleaned;
        } catch (Exception $e) {
            Log::error("Failed to cleanup monitoring data: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate estimated completion time.
     */
    private function calculateEstimatedCompletion(UpdateSession $session): ?string
    {
        if (!$session->started_at || !$session->progress_percentage || $session->progress_percentage <= 0) {
            return null;
        }

        $elapsed = $session->started_at->diffInSeconds(now());
        $progress = $session->progress_percentage / 100;
        
        $totalEstimated = $elapsed / $progress;
        $remaining = $totalEstimated - $elapsed;

        return now()->addSeconds($remaining)->toISOString();
    }

    /**
     * Calculate current duration.
     */
    private function calculateDuration(UpdateSession $session): string
    {
        if (!$session->started_at) {
            return '0s';
        }

        $diff = $session->started_at->diffInSeconds(now());
        
        if ($diff < 60) {
            return $diff . 's';
        } elseif ($diff < 3600) {
            return round($diff / 60) . 'm';
        } else {
            return round($diff / 3600, 1) . 'h';
        }
    }

    /**
     * Get current CPU usage.
     */
    private function getCurrentCpuUsage(): float
    {
        try {
            $load = sys_getloadavg();
            return round($load[0], 2);
        } catch (Exception $e) {
            return 0.0;
        }
    }

    /**
     * Get current memory usage.
     */
    private function getCurrentMemoryUsage(): array
    {
        return [
            'used' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => $this->getMemoryLimit(),
        ];
    }

    /**
     * Get current disk I/O.
     */
    private function getCurrentDiskIo(): array
    {
        // Placeholder implementation
        return [
            'reads' => 0,
            'writes' => 0,
            'read_bytes' => 0,
            'write_bytes' => 0,
        ];
    }

    /**
     * Get current network I/O.
     */
    private function getCurrentNetworkIo(): array
    {
        // Placeholder implementation
        return [
            'rx_bytes' => 0,
            'tx_bytes' => 0,
            'rx_packets' => 0,
            'tx_packets' => 0,
        ];
    }

    /**
     * Get system load average.
     */
    private function getLoadAverage(): array
    {
        try {
            $load = sys_getloadavg();
            return [
                '1min' => round($load[0], 2),
                '5min' => round($load[1], 2),
                '15min' => round($load[2], 2),
            ];
        } catch (Exception $e) {
            return ['1min' => 0, '5min' => 0, '15min' => 0];
        }
    }

    /**
     * Get update queue size.
     */
    private function getUpdateQueueSize(): int
    {
        // Placeholder - implement based on queue system
        return 0;
    }

    /**
     * Get cache usage information.
     */
    private function getCacheUsage(): array
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info('memory');
            
            return [
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'max_memory' => $info['maxmemory'] ?? 0,
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get cache key count.
     */
    private function getCacheKeyCount(): int
    {
        try {
            return count(Cache::getRedis()->keys(self::CACHE_PREFIX . "*"));
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get monitoring uptime.
     */
    private function getMonitoringUptime(): string
    {
        // Placeholder - implement based on monitoring start time
        return '0s';
    }

    /**
     * Get PHP memory limit.
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit == -1) {
            return PHP_INT_MAX;
        }
        
        return $this->convertToBytes($limit);
    }

    /**
     * Convert memory limit string to bytes.
     */
    private function convertToBytes(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}