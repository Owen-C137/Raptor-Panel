<?php

use Illuminate\Support\Facades\Route;
// Controllers will be added in Phase 2

/*
|--------------------------------------------------------------------------
| AI API Routes
|--------------------------------------------------------------------------
|
| API endpoints for AI functionality.
| Used by frontend JavaScript components for real-time AI interactions.
|
*/

Route::group([
    'prefix' => '/api/ai',
    'middleware' => ['auth'],
], function () {
    
    // Chat API endpoints (Phase 2)
    // Route::post('/chat/start', [AiChatController::class, 'startConversation'])
    //     ->name('api.ai.chat.start');
    
    // Route::post('/chat/{conversation}/message', [AiChatController::class, 'sendMessage'])
    //     ->name('api.ai.chat.message');
    
    // Route::get('/chat/{conversation}', [AiChatController::class, 'getConversation'])
    //     ->name('api.ai.chat.get');

    // Analysis API endpoints (Phase 3)
    // Route::post('/analyze/logs', [AiAnalysisController::class, 'analyzeLogs'])
    //     ->name('api.ai.analyze.logs');
    
    // Route::get('/insights/server/{server}', [AiAnalysisController::class, 'getServerInsights'])
    //     ->name('api.ai.insights.server');

});