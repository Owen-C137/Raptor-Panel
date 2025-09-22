<?php

namespace Pterodactyl\Services\Updates\Database;

use Carbon\Carbon;
use Pterodactyl\Exceptions\Updates\DatabaseOperationException;
use Pterodactyl\Models\UpdateSession;
use Pterodactyl\Services\Updates\BaseUpdateService;
use Ramsey\Uuid\Uuid;

/**
 * Session Service
 * 
 * Manages update session lifecycle and state tracking.
 */
class SessionService extends BaseUpdateService
{
    public function getServiceName(): string
    {
        return 'Session Service';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check if database connection is available
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }

        // Check if update_sessions table exists
        if (!\Schema::hasTable('update_sessions')) {
            $errors[] = 'update_sessions table does not exist';
        }

        return $errors;
    }

    /**
     * Create a new update session.
     */
    public function createSession(array $sessionData): UpdateSession
    {
        try {
            $this->logInfo('Creating new update session', [
                'from_version' => $sessionData['from_version'] ?? 'unknown',
                'to_version' => $sessionData['to_version'] ?? 'unknown'
            ]);

            // Validate required fields
            $required = ['from_version', 'to_version'];
            foreach ($required as $field) {
                if (!isset($sessionData[$field]) || empty($sessionData[$field])) {
                    throw new DatabaseOperationException("Required field '{$field}' is missing from session data");
                }
            }

            // Check if there's already an active session
            $activeSession = $this->getActiveSession();
            if ($activeSession) {
                throw new DatabaseOperationException("An active update session already exists (ID: {$activeSession->id})");
            }

            // Create session
            $session = UpdateSession::create([
                'session_id' => Uuid::uuid4()->toString(),
                'from_version' => $sessionData['from_version'],
                'to_version' => $sessionData['to_version'],
                'status' => 'pending',
                'started_at' => Carbon::now(),
                'initiated_by' => $sessionData['initiated_by'] ?? 'system',
                'update_type' => $sessionData['update_type'] ?? 'minor',
                'metadata' => $sessionData['metadata'] ?? null,
            ]);

            $this->logInfo('Update session created successfully', [
                'id' => $session->id,
                'session_id' => $session->session_id,
                'from_version' => $session->from_version,
                'to_version' => $session->to_version
            ]);

            return $session;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to create update session');
            throw new DatabaseOperationException('Failed to create update session: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the currently active update session.
     */
    public function getActiveSession(): ?UpdateSession
    {
        try {
            $session = UpdateSession::whereIn('status', ['pending', 'in_progress', 'paused'])
                ->orderBy('started_at', 'desc')
                ->first();

            if ($session) {
                $this->logDebug('Active session found', [
                    'id' => $session->id,
                    'session_id' => $session->session_id,
                    'status' => $session->status,
                    'from_version' => $session->from_version,
                    'to_version' => $session->to_version
                ]);
            }

            return $session;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get active session');
            throw new DatabaseOperationException('Failed to get active session: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update session status.
     */
    public function updateSessionStatus(string $sessionId, string $status, ?string $errorMessage = null): bool
    {
        try {
            $this->logInfo('Updating session status', [
                'session_id' => $sessionId,
                'status' => $status,
                'has_error' => !empty($errorMessage)
            ]);

            // Validate status
            $validStatuses = ['pending', 'in_progress', 'completed', 'failed', 'cancelled', 'paused'];
            if (!in_array($status, $validStatuses)) {
                throw new DatabaseOperationException("Invalid session status: {$status}");
            }

            $session = UpdateSession::where('session_id', $sessionId)->first();
            if (!$session) {
                throw new DatabaseOperationException("Session not found: {$sessionId}");
            }

            // Prepare update data
            $updateData = ['status' => $status];

            // Set completion time for final statuses
            if (in_array($status, ['completed', 'failed', 'cancelled'])) {
                $updateData['completed_at'] = Carbon::now();
            }

            // Set error message if provided
            if ($errorMessage) {
                $updateData['error_message'] = $errorMessage;
            }

            $session->update($updateData);

            $this->logInfo('Session status updated successfully', [
                'session_id' => $sessionId,
                'old_status' => $session->getOriginal('status'),
                'new_status' => $status,
                'completed_at' => $updateData['completed_at'] ?? null
            ]);

            return true;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to update session status');
            throw new DatabaseOperationException('Failed to update session status: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update session progress.
     */
    public function updateSessionProgress(string $sessionId, array $progress): bool
    {
        try {
            $this->logDebug('Updating session progress', [
                'session_id' => $sessionId,
                'progress' => $progress
            ]);

            $session = UpdateSession::where('session_id', $sessionId)->first();
            if (!$session) {
                throw new DatabaseOperationException("Session not found: {$sessionId}");
            }

            // Map progress data to correct database columns
            $updateData = [];
            
            if (isset($progress['percentage'])) {
                $updateData['progress_percentage'] = min(100, max(0, (int) $progress['percentage']));
            }
            
            if (isset($progress['current_step'])) {
                $updateData['current_step'] = $progress['current_step'];
            }
            
            if (isset($progress['total_steps'])) {
                $updateData['total_steps'] = (int) $progress['total_steps'];
            }
            
            if (isset($progress['completed_steps'])) {
                $updateData['completed_steps'] = (int) $progress['completed_steps'];
            }
            
            if (!empty($updateData)) {
                $updateData['updated_at'] = Carbon::now();
                $session->update($updateData);
                
                $this->logInfo('Session progress updated successfully', [
                    'session_id' => $sessionId,
                    'updated_fields' => array_keys($updateData)
                ]);
            }

            return true;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to update session progress');
            throw new DatabaseOperationException('Failed to update session progress: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get session by ID.
     */
    public function getSession(string $sessionId): ?UpdateSession
    {
        try {
            return UpdateSession::where('session_id', $sessionId)->first();
        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get session');
            throw new DatabaseOperationException('Failed to get session: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get session history.
     */
    public function getSessionHistory(int $limit = 20): array
    {
        try {
            $this->logInfo('Retrieving session history', ['limit' => $limit]);

            $sessions = UpdateSession::orderBy('started_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();

            $this->logDebug('Session history retrieved', ['count' => count($sessions)]);

            return $sessions;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get session history');
            throw new DatabaseOperationException('Failed to get session history: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Cancel an active session.
     */
    public function cancelActiveSession(?string $reason = null): bool
    {
        try {
            $this->logInfo('Cancelling active session', ['reason' => $reason]);

            $session = $this->getActiveSession();
            if (!$session) {
                $this->logWarning('No active session to cancel');
                return false;
            }

            $session->update([
                'status' => 'cancelled',
                'completed_at' => Carbon::now(),
                'error_message' => $reason ? "Cancelled: {$reason}" : 'Cancelled by user'
            ]);

            $this->logInfo('Session cancelled successfully', [
                'session_id' => $session->session_id,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to cancel active session');
            throw new DatabaseOperationException('Failed to cancel active session: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Resume a paused session.
     */
    public function resumeSession(string $sessionId): bool
    {
        try {
            $this->logInfo('Resuming session', ['session_id' => $sessionId]);

            $session = UpdateSession::where('session_id', $sessionId)->first();
            if (!$session) {
                throw new DatabaseOperationException("Session not found: {$sessionId}");
            }

            if ($session->status !== 'paused') {
                throw new DatabaseOperationException("Session is not paused (current status: {$session->status})");
            }

            $session->update(['status' => 'in_progress']);

            $this->logInfo('Session resumed successfully', ['session_id' => $sessionId]);

            return true;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to resume session');
            throw new DatabaseOperationException('Failed to resume session: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Pause an active session.
     */
    public function pauseSession(string $sessionId): bool
    {
        try {
            $this->logInfo('Pausing session', ['session_id' => $sessionId]);

            $session = UpdateSession::where('session_id', $sessionId)->first();
            if (!$session) {
                throw new DatabaseOperationException("Session not found: {$sessionId}");
            }

            if (!in_array($session->status, ['pending', 'in_progress'])) {
                throw new DatabaseOperationException("Cannot pause session with status: {$session->status}");
            }

            $session->update(['status' => 'paused']);

            $this->logInfo('Session paused successfully', ['session_id' => $sessionId]);

            return true;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to pause session');
            throw new DatabaseOperationException('Failed to pause session: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get session statistics.
     */
    public function getSessionStatistics(): array
    {
        try {
            $this->logInfo('Generating session statistics');

            $stats = [
                'total_sessions' => UpdateSession::count(),
                'completed_sessions' => UpdateSession::where('status', 'completed')->count(),
                'failed_sessions' => UpdateSession::where('status', 'failed')->count(),
                'cancelled_sessions' => UpdateSession::where('status', 'cancelled')->count(),
                'active_sessions' => UpdateSession::whereIn('status', ['pending', 'in_progress', 'paused'])->count(),
                'success_rate' => 0,
                'average_duration' => null,
                'last_update' => null,
            ];

            // Calculate success rate
            if ($stats['total_sessions'] > 0) {
                $stats['success_rate'] = round(($stats['completed_sessions'] / $stats['total_sessions']) * 100, 2);
            }

            // Calculate average duration for completed sessions
            $avgDuration = UpdateSession::where('status', 'completed')
                ->whereNotNull('completed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_seconds')
                ->first();

            if ($avgDuration && $avgDuration->avg_seconds) {
                $stats['average_duration'] = round($avgDuration->avg_seconds, 2);
            }

            // Get last successful update
            $lastUpdate = UpdateSession::where('status', 'completed')
                ->orderBy('completed_at', 'desc')
                ->first();

            if ($lastUpdate) {
                $stats['last_update'] = $lastUpdate->completed_at->toDateTimeString();
            }

            $this->logDebug('Session statistics generated', $stats);

            return $stats;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to generate session statistics');
            throw new DatabaseOperationException('Failed to generate session statistics: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Clean up old session records.
     */
    public function cleanupOldSessions(int $keepDays = 30): int
    {
        try {
            $this->logInfo('Cleaning up old session records', ['keep_days' => $keepDays]);

            $cutoffDate = Carbon::now()->subDays($keepDays);

            $deletedCount = UpdateSession::where('completed_at', '<', $cutoffDate)
                ->whereIn('status', ['completed', 'failed', 'cancelled'])
                ->delete();

            $this->logInfo('Session cleanup completed', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString()
            ]);

            return $deletedCount;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to cleanup old sessions');
            throw new DatabaseOperationException('Failed to cleanup old sessions: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get recent update sessions.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getRecentSessions(int $limit = 10)
    {
        try {
            return UpdateSession::orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get recent sessions');
            return collect();
        }
    }

    /**
     * Get all update sessions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllSessions()
    {
        try {
            return UpdateSession::orderBy('created_at', 'desc')->get();
        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get all sessions');
            return collect();
        }
    }

    /**
     * Get paginated update sessions.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedSessions(int $perPage = 15)
    {
        try {
            $this->logInfo('Getting paginated sessions', ['per_page' => $perPage]);

            return UpdateSession::orderBy('created_at', 'desc')->paginate($perPage);
        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get paginated sessions');
            
            // Return a fake paginator with empty data
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                ['path' => request()->url()]
            );
        }
    }

    /**
     * Get recent logs for a session.
     */
    public function getRecentLogs(string $sessionId, int $limit = 10): array
    {
        try {
            // For now, return simulated logs based on session progress
            $session = UpdateSession::where('session_id', $sessionId)->first();
            
            if (!$session) {
                return [];
            }

            $logs = [];
            
            // Add logs based on current progress
            if ($session->current_step) {
                $logs[] = [
                    'level' => 'info',
                    'message' => '[' . now()->format('H:i:s') . '] ' . $session->current_step,
                    'timestamp' => $session->updated_at
                ];
            }

            if ($session->progress_percentage > 0) {
                $logs[] = [
                    'level' => 'info', 
                    'message' => '[' . now()->format('H:i:s') . '] Progress: ' . $session->progress_percentage . '%',
                    'timestamp' => $session->updated_at
                ];
            }

            return array_slice($logs, 0, $limit);

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get recent logs');
            return [];
        }
    }

    /**
     * Get session steps information.
     */
    public function getSessionSteps(string $sessionId): array
    {
        try {
            $session = UpdateSession::where('session_id', $sessionId)->first();
            
            if (!$session) {
                return [];
            }

            // Return basic step information
            return [
                'total_steps' => $session->total_steps ?? 5,
                'completed_steps' => $session->completed_steps ?? 0,
                'current_step_name' => $session->current_step ?? 'Initializing...',
                'progress_percentage' => $session->progress_percentage ?? 0
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get session steps');
            return [];
        }
    }
}