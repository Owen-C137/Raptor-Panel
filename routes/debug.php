<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-version', function () {
    return response()->json([
        'config_version' => config('app.version'),
        'view_shared' => view()->getShared()['appVersion'] ?? 'not found',
        'cache_git_version' => \Cache::get('git-version'),
        'time' => now()->toDateTimeString()
    ]);
});