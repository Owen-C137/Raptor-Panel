<?php

use Illuminate\Support\Facades\Route;
use PterodactylAddons\ModManager\Http\Controllers\Admin\ModManagerController;
use PterodactylAddons\ModManager\Http\Controllers\Admin\DirectHarvestController;

/*
|--------------------------------------------------------------------------
| Mod Manager Admin Web Routes
|--------------------------------------------------------------------------
|
| All mod manager routes consolidated here for clean organization.
| These routes are loaded by the ModManagerServiceProvider with admin middleware.
|
*/

// Main dashboard route
Route::get('/', [ModManagerController::class, 'index'])->name('index');
Route::get('/dashboard', [ModManagerController::class, 'index'])->name('dashboard');

// API endpoints for admin interface
Route::get('/live-stats', [ModManagerController::class, 'liveStats']);
Route::get('/harvest-history', [ModManagerController::class, 'harvestHistory']);
Route::get('/game-details/{gameId}', [ModManagerController::class, 'gameDetails']);
Route::get('/system-health', [ModManagerController::class, 'systemHealth']);

// Direct harvest endpoints
Route::get('/harvest-complete', [DirectHarvestController::class, 'harvestComplete']);
Route::post('/harvest-stop', [DirectHarvestController::class, 'stopHarvest']);