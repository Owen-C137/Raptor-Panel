<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin\Updates\UpdateDashboardController;
use Pterodactyl\Http\Controllers\Admin\Updates\UpdateController;

/*
|--------------------------------------------------------------------------
| Update System Routes
|--------------------------------------------------------------------------
|
| These routes handle the update system UI and API endpoints for the
| Pterodactyl panel. All routes require admin authentication and
| appropriate permissions for update management.
|
*/

Route::group([
    'prefix' => 'admin/updates',
    'middleware' => ['auth', 'admin'],
], function () {
    
    // Main Navigation Routes (UI Views)
    Route::get('/', [UpdateDashboardController::class, 'index'])->name('admin.updates.dashboard');
    Route::get('/manage', [UpdateDashboardController::class, 'manage'])->name('admin.updates.manage');
    Route::get('/confirm/{version}', [UpdateDashboardController::class, 'showConfirmUpdate'])->name('admin.updates.confirm');
    Route::get('/history', [UpdateDashboardController::class, 'history'])->name('admin.updates.history');
    Route::get('/health', [UpdateDashboardController::class, 'healthView'])->name('admin.updates.health');
    Route::get('/safety', [UpdateDashboardController::class, 'safety'])->name('admin.updates.safety');
    Route::get('/configuration', [UpdateDashboardController::class, 'configuration'])->name('admin.updates.configuration');
    
    // Session Details Route
    Route::get('/history/{session}', function($sessionId) { 
        return view('admin.updates.session-details', ['sessionId' => $sessionId, 'activeTab' => 'history']); 
    })->name('admin.updates.session-details');
    
    // API Routes (prefixed with api/)
    Route::prefix('api')->group(function () {
        // Dashboard API Routes
        Route::get('/overview', [UpdateDashboardController::class, 'overview'])->name('admin.updates.api.overview');
        Route::get('/status', [UpdateDashboardController::class, 'status'])->name('admin.updates.api.status');
        Route::get('/health', [UpdateDashboardController::class, 'health'])->name('admin.updates.api.health');
        Route::get('/system-health-overview', [UpdateDashboardController::class, 'systemHealthOverview'])->name('admin.updates.api.system-health-overview');
        Route::get('/statistics', [UpdateDashboardController::class, 'statistics'])->name('admin.updates.api.statistics');
        Route::get('/activity', [UpdateDashboardController::class, 'activity'])->name('admin.updates.api.activity');
        Route::get('/config-status', [UpdateDashboardController::class, 'configStatus'])->name('admin.updates.api.config-status');
        Route::get('/backups', [UpdateDashboardController::class, 'backups'])->name('admin.updates.api.backups');
        Route::get('/available-updates', [UpdateDashboardController::class, 'getAvailableUpdates'])->name('admin.updates.api.available-updates');
        Route::post('/clear-cache', [UpdateDashboardController::class, 'clearCache'])->name('admin.updates.api.clear-cache');
        
        // Update Management API Routes
        Route::get('/check', [UpdateController::class, 'checkForUpdates'])->name('admin.updates.api.check');
        Route::post('/start', [UpdateController::class, 'startUpdate'])->name('admin.updates.api.start');
        Route::post('/cancel/{sessionId}', [UpdateController::class, 'cancelUpdate'])->name('admin.updates.api.cancel');
        Route::post('/rollback/{sessionId}', [UpdateController::class, 'rollbackUpdate'])->name('admin.updates.api.rollback');
        Route::post('/emergency-stop', [UpdateController::class, 'emergencyStop'])->name('admin.updates.api.emergency-stop');
        
        // Configuration API Routes  
        Route::post('/config', [UpdateController::class, 'updateConfiguration'])->name('admin.updates.api.config');
        Route::get('/config', [UpdateController::class, 'getConfiguration'])->name('admin.updates.api.get-config');
        Route::post('/configuration/update', [UpdateController::class, 'updateConfiguration'])->name('admin.updates.configuration.update');
        Route::post('/notifications/update', [UpdateController::class, 'updateNotificationSettings'])->name('admin.updates.notifications.update');
        
        // History API Routes
        Route::get('/sessions', [UpdateController::class, 'getSessions'])->name('admin.updates.api.sessions');
        Route::get('/sessions/{sessionId}', [UpdateController::class, 'getSessionDetails'])->name('admin.updates.api.session-details');
        Route::get('/sessions/{sessionId}/logs', [UpdateController::class, 'getSessionLogs'])->name('admin.updates.api.session-logs');
        Route::delete('/sessions/{sessionId}', [UpdateController::class, 'deleteSession'])->name('admin.updates.api.delete-session');
        
        // Progress Tracking API Routes
        Route::get('/progress/{sessionId}', [UpdateController::class, 'getProgress'])->name('admin.updates.api.progress');
        Route::get('/status/{sessionId}', [UpdateController::class, 'getSessionStatus'])->name('admin.updates.api.session-status');
    });

    // Direct API Routes (used by dashboard JavaScript - without api/ prefix)
    Route::get('/status', [UpdateDashboardController::class, 'status'])->name('admin.updates.status');
    Route::get('/health-data', [UpdateDashboardController::class, 'health'])->name('admin.updates.health-data');
    Route::get('/current-progress', [UpdateController::class, 'getCurrentProgress'])->name('admin.updates.current-progress');
    Route::post('/initiate', [UpdateController::class, 'initiateUpdate'])->name('admin.updates.initiate');
    Route::get('/progress-page/{sessionId}', [UpdateController::class, 'showProgressPage'])->name('admin.updates.progress-page');
    Route::post('/rollback/{sessionId}', [UpdateController::class, 'rollbackUpdate'])->name('admin.updates.rollback');
    Route::post('/stop', [UpdateController::class, 'emergencyStop'])->name('admin.updates.stop');
    Route::post('/health-check', [UpdateDashboardController::class, 'runHealthCheck'])->name('admin.updates.health-check');
    Route::post('/check', [UpdateController::class, 'checkForUpdates'])->name('admin.updates.check');
    Route::post('/test', [UpdateController::class, 'runSystemTest'])->name('admin.updates.test');

    // Export and Download Routes
    Route::get('/export-history', [UpdateController::class, 'exportHistory'])->name('admin.updates.export-history');
    Route::get('/session-logs/{sessionId}', [UpdateController::class, 'getSessionLogs'])->name('admin.updates.session-logs');
    Route::get('/download-logs/{sessionId}', [UpdateController::class, 'downloadLogs'])->name('admin.updates.download-logs');
    Route::get('/download-session/{sessionId}', [UpdateController::class, 'downloadSession'])->name('admin.updates.download-session');

    // Health System Routes
    Route::get('/health/performance', [UpdateDashboardController::class, 'healthPerformance'])->name('admin.updates.health.performance');
    Route::post('/health/refresh', [UpdateDashboardController::class, 'refreshHealth'])->name('admin.updates.health.refresh');
    Route::post('/health/action', [UpdateDashboardController::class, 'healthAction'])->name('admin.updates.health.action');
    Route::get('/health/processes', [UpdateDashboardController::class, 'healthProcesses'])->name('admin.updates.health.processes');
    Route::get('/health/dependencies', [UpdateDashboardController::class, 'healthDependencies'])->name('admin.updates.health.dependencies');
    Route::get('/health/service-logs', [UpdateDashboardController::class, 'healthServiceLogs'])->name('admin.updates.health.service-logs');
    Route::get('/health/details/{checkId}', [UpdateDashboardController::class, 'healthDetails'])->name('admin.updates.health.details');
    Route::get('/health/export', [UpdateDashboardController::class, 'exportHealth'])->name('admin.updates.health.export');

    // Configuration Update Routes (non-duplicated ones only)
    Route::post('/health-checks/update', [UpdateController::class, 'updateHealthCheckSettings'])->name('admin.updates.health-checks.update');
    Route::post('/advanced/update', [UpdateController::class, 'updateAdvancedSettings'])->name('admin.updates.advanced.update');
    Route::post('/advanced/reset', [UpdateController::class, 'resetAdvancedSettings'])->name('admin.updates.advanced.reset');

    // Schedule Management Routes
    Route::post('/schedules', [UpdateController::class, 'storeSchedule'])->name('admin.updates.schedules.store');
    Route::post('/schedules/{scheduleId}/toggle', [UpdateController::class, 'toggleSchedule'])->name('admin.updates.schedules.toggle');
    Route::delete('/schedules/{scheduleId}', [UpdateController::class, 'destroySchedule'])->name('admin.updates.schedules.destroy');

    // Safety and Emergency Routes
    Route::post('/emergency-action', [UpdateController::class, 'emergencyAction'])->name('admin.updates.emergency-action');
    Route::post('/emergency-backup', [UpdateController::class, 'emergencyBackup'])->name('admin.updates.emergency-backup');
    Route::get('/rollback/info/{rollbackId}', [UpdateController::class, 'getRollbackInfo'])->name('admin.updates.rollback.info');
    Route::post('/rollback/execute/{rollbackId}', [UpdateController::class, 'executeRollback'])->name('admin.updates.rollback.execute');
    Route::delete('/rollback/{rollbackId}', [UpdateController::class, 'deleteRollback'])->name('admin.updates.rollback.delete');
    Route::get('/rollback/settings', [UpdateController::class, 'getRollbackSettings'])->name('admin.updates.rollback.settings');
    Route::get('/rollback-steps/{sessionId}', [UpdateController::class, 'getRollbackSteps'])->name('admin.updates.rollback-steps');

    // Safety Configuration Routes
    Route::post('/safety-config', [UpdateController::class, 'updateSafetyConfig'])->name('admin.updates.safety-config');
    Route::post('/safety-checks', [UpdateController::class, 'runSafetyChecks'])->name('admin.updates.safety-checks');

    // Progress and Session Tracking
    Route::get('/session-progress/{sessionId}', [UpdateController::class, 'getSessionProgress'])->name('admin.updates.session-progress');

    // Notification Routes
    Route::post('/test-notification', [UpdateController::class, 'testNotification'])->name('admin.updates.test-notification');
});