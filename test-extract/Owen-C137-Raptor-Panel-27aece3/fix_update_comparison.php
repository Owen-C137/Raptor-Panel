<?php

// This script demonstrates the fix for the file comparison issue
// The problem is that we're comparing base_path() (148K files) with the update directory
// Instead, we should only compare the directories that should actually be updated

$basePath = '/var/www/raptorpanel_dev';
$updatePath = '/var/www/raptorpanel_dev/storage/app/updates/temp/46315cb6-e175-4565-8028-0bc9a27cb0ed/Owen-C137-Raptor-Panel-6281879';

// These are the directories that should be updated
$updateableDirectories = [
    'app',
    'config', 
    'database',
    'public',
    'resources',
    'routes',
    'bootstrap'
];

// These are the root files that should be updated
$updateableFiles = [
    'artisan',
    'composer.json',
    'composer.lock',
    'package.json',
    'package-lock.json',
    '.env.example',
    'webpack.config.js',
    'tailwind.config.js',
    'babel.config.js',
    'postcss.config.js',
    '.eslintrc.js',
    '.prettierrc.json',
    'tsconfig.json'
];

// Count files in each approach
echo "=== CURRENT PROBLEMATIC APPROACH ===\n";
$currentFiles = shell_exec("find $basePath -type f | wc -l");
echo "Total files in base_path(): " . trim($currentFiles) . " files\n";

echo "\n=== PROPOSED FIXED APPROACH ===\n";
$totalUpdateableFiles = 0;

foreach ($updateableDirectories as $dir) {
    $count = shell_exec("find $basePath/$dir -type f 2>/dev/null | wc -l");
    $count = trim($count);
    echo "Files in $dir/: $count\n";
    $totalUpdateableFiles += (int)$count;
}

$rootFileCount = 0;
foreach ($updateableFiles as $file) {
    if (file_exists("$basePath/$file")) {
        $rootFileCount++;
    }
}
echo "Root files: $rootFileCount\n";
$totalUpdateableFiles += $rootFileCount;

echo "\nTotal files that should be compared: $totalUpdateableFiles files\n";
echo "Reduction: " . (trim($currentFiles) - $totalUpdateableFiles) . " files eliminated\n";

echo "\n=== WHAT'S EXCLUDED ===\n";
$excludedDirs = ['vendor', 'node_modules', 'storage', '.git'];
foreach ($excludedDirs as $dir) {
    if (is_dir("$basePath/$dir")) {
        $count = shell_exec("find $basePath/$dir -type f 2>/dev/null | wc -l");
        echo "Excluded $dir/: " . trim($count) . " files\n";
    }
}