<?php

use Illuminate\Support\Facades\Route;
use PterodactylAddons\ModManager\Http\Controllers\Admin\ModManagerController;

/*
|--------------------------------------------------------------------------
| Mod Manager Admin API Routes
|--------------------------------------------------------------------------
|
| Clean routes file for the new streamlined direct harvest system.
| All old job/queue/worker complexity has been removed.
|
*/

// Main Admin Interface API Routes
Route::get('/games', [ModManagerController::class, 'apiGames'])->name('games');
Route::get('/stats', [ModManagerController::class, 'apiStats'])->name('stats');
Route::get('/live-stats', [ModManagerController::class, 'liveStats'])->name('live-stats');

// Note: Direct harvest routes are now handled in main routes/admin.php
// This keeps all routing centralized and avoids controller conflicts.