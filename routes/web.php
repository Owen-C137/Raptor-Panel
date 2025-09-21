
Route::get('/debug-version', function () {
    return response()->json([
        'config_version' => config('app.version'),
        'view_shared' => view()->getShared()['appVersion'] ?? 'not found',
        'cache_git_version' => \Cache::get('git-version'),
        'time' => now()->toDateTimeString()
    ]);
});

Route::get('/debug-version-direct', function () {
    $configVersion = config('app.version');
    $versionData = app(Pterodactyl\\Providers\\AppServiceProvider::class);
    
    return response()->json([
        'config_direct' => $configVersion,
        'config_exists' => file_exists(config_path('app.php')),
        'config_readable' => is_readable(config_path('app.php')),
        'app_env' => app()->environment(),
        'cached_config' => config()->all()['app']['version'] ?? 'not found',
        'view_shared_appVersion' => view()->getShared()['appVersion'] ?? 'not found in shared',
        'time' => now(),
        'git_head_exists' => file_exists(base_path('.git/HEAD')),
        'process_id' => getmypid()
    ]);
});
