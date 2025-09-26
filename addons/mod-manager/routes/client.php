<?php

use Illuminate\Support\Facades\Route;
use PterodactylAddons\ModManager\Http\Controllers\Client\ModManagerController;

/*
|--------------------------------------------------------------------------
| Mod Manager Client API Routes
|--------------------------------------------------------------------------
|
| Client-side API routes for mod management functionality.
| These routes allow users to manage mods on their servers.
|
*/

Route::group(['prefix' => '/api/client/servers/{server}/mods'], function () {
    Route::get('/installed', [ModManagerController::class, 'getInstalledMods'])
        ->name('api.client.servers.mods.installed');
    
    Route::get('/available', [ModManagerController::class, 'getAvailableMods'])
        ->name('api.client.servers.mods.available');
    
    Route::post('/install', [ModManagerController::class, 'installMod'])
        ->name('api.client.servers.mods.install');
    
    Route::delete('/uninstall', [ModManagerController::class, 'uninstallMod'])
        ->name('api.client.servers.mods.uninstall');
});