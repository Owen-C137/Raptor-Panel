<?php

use Illuminate\Support\Facades\Route;
use PterodactylAddons\OllamaAi\Http\Controllers\Client\AiChatController;
use PterodactylAddons\OllamaAi\Http\Controllers\Client\AiHelpController;

/*
|--------------------------------------------------------------------------
| Client AI Routes  
|--------------------------------------------------------------------------
|
| Routes for client-facing AI functionality.
| All routes are prefixed and require user authentication.
|
*/

Route::group([
    'prefix' => '/ai',
    'middleware' => ['auth', 'throttle:60,1'],
], function () {
    
    // Main AI interface (redirects to chat)
    Route::get('/', [AiChatController::class, 'index'])->name('client.ai.index');
    
    // Main chat interface
    Route::get('/chat', [AiChatController::class, 'index'])->name('client.ai.chat');
    
    // Chat functionality
    Route::post('/send', [AiChatController::class, 'sendMessage'])->name('client.ai.send');
    Route::get('/status', [AiChatController::class, 'getStatus'])->name('client.ai.status');
    Route::get('/suggestions', [AiChatController::class, 'getQuickSuggestions'])->name('client.ai.suggestions');
    
    // Conversation management
    Route::post('/conversations/new', [AiChatController::class, 'newConversation'])->name('client.ai.conversations.new');
    Route::get('/conversations/{conversation}', [AiChatController::class, 'getConversation'])->name('client.ai.conversations.show');
    Route::delete('/conversations/{conversation}', [AiChatController::class, 'deleteConversation'])->name('client.ai.conversations.delete');
    
    // AI Help System (Phase 4)
    Route::get('/help', [AiHelpController::class, 'learningDashboard'])->name('client.ai.help');
    Route::get('/help/contextual', [AiHelpController::class, 'getContextualHelp'])->name('client.ai.help.contextual');
    Route::get('/help/suggestions', [AiHelpController::class, 'getIntelligentSuggestions'])->name('client.ai.help.suggestions');
    Route::get('/help/tutorial', [AiHelpController::class, 'getTutorial'])->name('client.ai.help.tutorial');
    Route::get('/help/documentation', [AiHelpController::class, 'getSmartDocumentation'])->name('client.ai.help.documentation');
    Route::get('/help/quick', [AiHelpController::class, 'getQuickHelp'])->name('client.ai.help.quick');
    Route::post('/help/progress', [AiHelpController::class, 'trackProgress'])->name('client.ai.help.progress');
    Route::get('/help/topics', [AiHelpController::class, 'getAvailableTopics'])->name('client.ai.help.topics');
    Route::get('/help/topic/{topic}/progress', [AiHelpController::class, 'getTopicProgress'])->name('client.ai.help.topic.progress');
    Route::get('/help/recommendations', [AiHelpController::class, 'getLearningRecommendations'])->name('client.ai.help.recommendations');

});