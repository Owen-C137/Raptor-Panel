<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiSettingsController;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiDashboardController;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiAnalysisController;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiPredictiveAnalyticsController;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiCustomReportController;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiHelpSystemController;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiOptimizationController;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiConversationController;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiCodeGenerationController;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiTemplateController;
use PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiChatController;

/*
|--------------------------------------------------------------------------
| Admin AI Routes
|--------------------------------------------------------------------------
|
| Routes for AI administration functionality.
| All routes are prefixed with 'admin/ai' and require admin authentication.
| Middleware is applied by the service provider.
|
*/

// Main AI admin index (redirects to dashboard)
Route::get('/', [AiDashboardController::class, 'index'])
    ->name('admin.ai.index');

// Dashboard routes
Route::get('/dashboard', [AiDashboardController::class, 'index'])
    ->name('admin.ai.dashboard');

Route::get('/dashboard/data', [AiDashboardController::class, 'getData'])
        ->name('admin.ai.dashboard.data');
    
    Route::get('/dashboard/activity', [AiDashboardController::class, 'getActivity'])
        ->name('admin.ai.dashboard.activity');
    
    Route::get('/system-info', [AiDashboardController::class, 'getSystemInfo'])
        ->name('admin.ai.system-info');
    
    Route::get('/health-check', [AiDashboardController::class, 'healthCheck'])
        ->name('admin.ai.health-check');
    
    // Server Analysis routes
    Route::get('/analysis', [AiAnalysisController::class, 'index'])
        ->name('admin.ai.analysis');
    
    Route::post('/analysis/server/{server}', [AiAnalysisController::class, 'analyzeServer'])
        ->name('admin.ai.analysis.server');
    
    Route::get('/analysis/server/{server}/insights', [AiAnalysisController::class, 'getServerInsights'])
        ->name('admin.ai.analysis.server.insights');
    
    Route::post('/analysis/bulk', [AiAnalysisController::class, 'bulkAnalyze'])
        ->name('admin.ai.analysis.bulk');
    
    Route::get('/analysis/stats', [AiAnalysisController::class, 'getAnalysisStats'])
        ->name('admin.ai.analysis.stats');
    
    
    // Conversations management routes
    Route::get('/conversations', [AiConversationController::class, 'index'])
        ->name('admin.ai.conversations.index');
    
    Route::get('/conversations/data', [AiConversationController::class, 'getData'])
        ->name('admin.ai.conversations.data');
    
    Route::get('/conversations/{conversation}', [AiConversationController::class, 'show'])
        ->name('admin.ai.conversations.show');
    
    Route::delete('/conversations/{conversation}', [AiConversationController::class, 'destroy'])
        ->name('admin.ai.conversations.destroy');
    
    Route::post('/conversations/bulk-delete', [AiConversationController::class, 'bulkDestroy'])
        ->name('admin.ai.conversations.bulk-destroy');
    
    // Admin Chat Interface routes (direct AI interaction for admins)
    Route::get('/chat', [AiChatController::class, 'index'])
        ->name('admin.ai.chat.index');
    
    Route::post('/chat/send', [AiChatController::class, 'sendMessage'])
        ->name('admin.ai.chat.send');
    
    Route::post('/chat/conversation/new', [AiChatController::class, 'newConversation'])
        ->name('admin.ai.chat.conversation.new');
    
    Route::get('/chat/conversation/{conversation}', [AiChatController::class, 'getConversation'])
        ->name('admin.ai.chat.conversation.show');
    
    Route::delete('/chat/conversation/{conversation}', [AiChatController::class, 'deleteConversation'])
        ->name('admin.ai.chat.conversation.delete');
    
    Route::get('/chat/models', [AiChatController::class, 'models'])
        ->name('admin.ai.chat.models');
    
    Route::get('/chat/status', [AiChatController::class, 'status'])
        ->name('admin.ai.chat.status');
    
    // Code Generation management routes
    Route::get('/code-generation', [AiCodeGenerationController::class, 'index'])
        ->name('admin.ai.code-generation.index');
    
    Route::get('/code-generation/data', [AiCodeGenerationController::class, 'getData'])
        ->name('admin.ai.code-generation.data');
    
    Route::get('/code-generation/{generation}', [AiCodeGenerationController::class, 'show'])
        ->name('admin.ai.code-generation.show');
    
    Route::delete('/code-generation/{generation}', [AiCodeGenerationController::class, 'destroy'])
        ->name('admin.ai.code-generation.destroy');
    
    Route::post('/code-generation/bulk-delete', [AiCodeGenerationController::class, 'bulkDestroy'])
        ->name('admin.ai.code-generation.bulk-destroy');
    
    // Templates management routes
    Route::get('/templates', [AiTemplateController::class, 'index'])
        ->name('admin.ai.templates.index');
    
    Route::get('/templates/data', [AiTemplateController::class, 'getData'])
        ->name('admin.ai.templates.data');
    
    Route::get('/templates/create', [AiTemplateController::class, 'create'])
        ->name('admin.ai.templates.create');
    
    Route::post('/templates', [AiTemplateController::class, 'store'])
        ->name('admin.ai.templates.store');
    
    Route::get('/templates/{template}', [AiTemplateController::class, 'show'])
        ->name('admin.ai.templates.show');
    
    Route::get('/templates/{template}/edit', [AiTemplateController::class, 'edit'])
        ->name('admin.ai.templates.edit');
    
    Route::put('/templates/{template}', [AiTemplateController::class, 'update'])
        ->name('admin.ai.templates.update');
    
    Route::delete('/templates/{template}', [AiTemplateController::class, 'destroy'])
        ->name('admin.ai.templates.destroy');
    
    Route::post('/templates/bulk-delete', [AiTemplateController::class, 'bulkDestroy'])
        ->name('admin.ai.templates.bulk-destroy');
    
    // Main AI settings page
    Route::get('/', [AiSettingsController::class, 'index'])
        ->name('admin.ai.index');
    
    Route::get('/settings', [AiSettingsController::class, 'index'])
        ->name('admin.ai.settings');

    Route::post('/settings', [AiSettingsController::class, 'update'])
        ->name('admin.ai.settings.update');

    // System status and testing
    Route::post('/test-connection', [AiSettingsController::class, 'testConnection'])
        ->name('admin.ai.test-connection');
    
    Route::post('/test-ai', [AiSettingsController::class, 'testAi'])
        ->name('admin.ai.test-ai');

    Route::get('/validate', [AiSettingsController::class, 'validateConfig'])
        ->name('admin.ai.validate');

    Route::get('/stats', [AiSettingsController::class, 'getStats'])
        ->name('admin.ai.stats');

// Model management
Route::get('/models', [AiSettingsController::class, 'showModels'])
    ->name('admin.ai.models');

Route::get('/models/library', [AiSettingsController::class, 'modelLibrary'])
    ->name('admin.ai.models.library');

Route::post('/models/library/refresh', [AiSettingsController::class, 'refreshLibrary'])
    ->name('admin.ai.models.library.refresh');

Route::get('/models/data', [AiSettingsController::class, 'getModels'])
    ->name('admin.ai.models.data');

Route::post('/models/pull', [AiSettingsController::class, 'pullModel'])
    ->name('admin.ai.pull-model');

Route::get('/models/pull-progress', [AiSettingsController::class, 'pullModelProgress'])
    ->name('admin.ai.pull-model-progress');
    
    Route::delete('/models/{model}', [AiSettingsController::class, 'deleteModel'])
        ->name('admin.ai.remove-model');
    
    Route::get('/models/{model}/info', [AiSettingsController::class, 'modelInfo'])
        ->name('admin.ai.models.info');

    // Predictive Analytics routes
    Route::get('/predictive-analytics', [AiPredictiveAnalyticsController::class, 'index'])
        ->name('admin.ai.predictive-analytics');
    
    Route::post('/predictive-analytics/server/{server}', [AiPredictiveAnalyticsController::class, 'generateServerPredictions'])
        ->name('admin.ai.predictive-analytics.generate');
    
    Route::get('/predictive-analytics/server/{server}', [AiPredictiveAnalyticsController::class, 'getServerPredictions'])
        ->name('admin.ai.predictive-analytics.server');
    
    Route::post('/predictive-analytics/bulk', [AiPredictiveAnalyticsController::class, 'generateBulkPredictions'])
        ->name('admin.ai.predictive-analytics.bulk-generate');
    
    Route::get('/predictive-analytics/alerts', [AiPredictiveAnalyticsController::class, 'getPredictiveAlerts'])
        ->name('admin.ai.predictive-analytics.alerts');
    
    Route::get('/predictive-analytics/trends', [AiPredictiveAnalyticsController::class, 'getPredictionTrends'])
        ->name('admin.ai.predictive-analytics.trends');
    
    Route::get('/predictive-analytics/export', [AiPredictiveAnalyticsController::class, 'exportPredictions'])
        ->name('admin.ai.predictive-analytics.export');

    // Custom Reports routes
    Route::get('/custom-reports', [AiCustomReportController::class, 'index'])
        ->name('admin.ai.custom-reports');
    
    Route::get('/custom-reports/create', [AiCustomReportController::class, 'create'])
        ->name('admin.ai.custom-reports.create');
    
    Route::post('/custom-reports/generate', [AiCustomReportController::class, 'generate'])
        ->name('admin.ai.custom-reports.generate');
    
    Route::get('/custom-reports/templates', [AiCustomReportController::class, 'getTemplates'])
        ->name('admin.ai.custom-reports.templates');
    
    Route::get('/custom-reports/template/{template}', [AiCustomReportController::class, 'getTemplate'])
        ->name('admin.ai.custom-reports.template');
    
    Route::get('/custom-reports/view/{reportId}', [AiCustomReportController::class, 'view'])
        ->name('admin.ai.custom-reports.view');
    
    Route::get('/custom-reports/export/{reportId}', [AiCustomReportController::class, 'export'])
        ->name('admin.ai.custom-reports.export');
    
    Route::post('/custom-reports/schedule', [AiCustomReportController::class, 'schedule'])
        ->name('admin.ai.custom-reports.schedule');
    
    Route::get('/custom-reports/history', [AiCustomReportController::class, 'history'])
        ->name('admin.ai.custom-reports.history');
    
    Route::delete('/custom-reports/{reportId}', [AiCustomReportController::class, 'delete'])
        ->name('admin.ai.custom-reports.delete');
    
    Route::get('/custom-reports/statistics', [AiCustomReportController::class, 'statistics'])
        ->name('admin.ai.custom-reports.statistics');
    
    Route::post('/custom-reports/duplicate/{reportId}', [AiCustomReportController::class, 'duplicate'])
        ->name('admin.ai.custom-reports.duplicate');
    
    Route::post('/custom-reports/compare', [AiCustomReportController::class, 'compare'])
        ->name('admin.ai.custom-reports.compare');

    // Data management
    Route::post('/cleanup', [AiSettingsController::class, 'cleanup'])
        ->name('admin.ai.cleanup');
    
    Route::post('/archive-old', [AiSettingsController::class, 'archiveOld'])
        ->name('admin.ai.archive-old');

    // Help System Management (Phase 4)
    Route::get('/help-system', [\PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiHelpSystemController::class, 'index'])
        ->name('admin.ai.help-system');
        
    Route::get('/help-system/analytics', [\PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiHelpSystemController::class, 'analytics'])
        ->name('admin.ai.help-system.analytics');
        
    Route::get('/help-system/tutorials', [\PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiHelpSystemController::class, 'tutorials'])
        ->name('admin.ai.help-system.tutorials');
        
    Route::post('/help-system/generate', [\PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiHelpSystemController::class, 'generateHelp'])
        ->name('admin.ai.help-system.generate');
        
    Route::post('/help-system/suggestions', [\PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiHelpSystemController::class, 'getIntelligentSuggestions'])
        ->name('admin.ai.help-system.suggestions');
        
    Route::post('/help-system/tutorial', [\PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiHelpSystemController::class, 'generateTutorial'])
        ->name('admin.ai.help-system.tutorial');
        
    Route::post('/help-system/documentation', [\PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiHelpSystemController::class, 'getSmartDocumentation'])
        ->name('admin.ai.help-system.documentation');
        
    Route::get('/help-system/user/{user}/progress', [\PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiHelpSystemController::class, 'userLearningProgress'])
        ->name('admin.ai.help-system.user.progress');
        
        Route::get('/help-system/export', [\PterodactylAddons\OllamaAi\Http\Controllers\Admin\AiHelpSystemController::class, 'exportAnalytics'])
        ->name('admin.ai.help-system.export');

    // Phase 5: Optimization & Polish Routes
    Route::get('/optimization', [AiOptimizationController::class, 'index'])
        ->name('admin.ai.optimization');

    Route::get('/optimization/performance', [AiOptimizationController::class, 'performanceDashboard'])
        ->name('admin.ai.optimization.performance');

    Route::get('/optimization/ui-ux', [AiOptimizationController::class, 'uiUxDashboard'])
        ->name('admin.ai.optimization.ui-ux');

    Route::get('/optimization/testing', [AiOptimizationController::class, 'testingDashboard'])
        ->name('admin.ai.optimization.testing');

    Route::post('/optimization/performance', [AiOptimizationController::class, 'optimizePerformance'])
        ->name('admin.ai.optimization.optimize-performance');

    Route::post('/optimization/ui-ux', [AiOptimizationController::class, 'optimizeUiUx'])
        ->name('admin.ai.optimization.optimize-ui-ux');

    Route::post('/optimization/tests', [AiOptimizationController::class, 'runTests'])
        ->name('admin.ai.optimization.run-tests');

    Route::get('/optimization/performance/metrics', [AiOptimizationController::class, 'performanceMetrics'])
        ->name('admin.ai.optimization.performance.metrics');

    Route::get('/optimization/ui-ux/metrics', [AiOptimizationController::class, 'uiUxMetrics'])
        ->name('admin.ai.optimization.ui-ux.metrics');

    Route::get('/optimization/testing/metrics', [AiOptimizationController::class, 'testingMetrics'])
        ->name('admin.ai.optimization.testing.metrics');

    Route::post('/optimization/report', [AiOptimizationController::class, 'generateOptimizationReport'])
        ->name('admin.ai.optimization.generate-report');

    Route::post('/optimization/export', [AiOptimizationController::class, 'exportOptimizationData'])
        ->name('admin.ai.optimization.export');

    Route::post('/optimization/reset-baselines', [AiOptimizationController::class, 'resetBaselines'])
        ->name('admin.ai.optimization.reset-baselines');