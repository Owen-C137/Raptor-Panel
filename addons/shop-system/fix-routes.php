<?php
/**
 * Shop System Route Fix Script V2
 * Finds and fixes ALL controller references in the entire shop system
 */

echo "🔧 Shop System Route Fix Script V2\n";
echo "===================================\n\n";

$baseDir = '/var/www/pterodactyl/addons/shop-system';
$searchDirs = [
    $baseDir . '/routes',
    $baseDir . '/src/Providers'
];

$controllerDir = $baseDir . '/src/Http/Controllers';

// Step 1: Scan existing controllers
echo "📂 Scanning existing controllers...\n";
$existingControllers = [];

function scanControllers($dir, $namespace = '') {
    global $existingControllers;
    
    if (!is_dir($dir)) return;
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $fullPath = $dir . '/' . $file;
        if (is_dir($fullPath)) {
            $subNamespace = $namespace ? $namespace . '\\' . $file : $file;
            scanControllers($fullPath, $subNamespace);
        } elseif (str_ends_with($file, '.php')) {
            $className = str_replace('.php', '', $file);
            $fullClassName = $namespace ? $namespace . '\\' . $className : $className;
            $existingControllers[$fullClassName] = $fullPath;
            echo "  ✓ Found: {$fullClassName}\n";
        }
    }
}

scanControllers($controllerDir);
echo "Found " . count($existingControllers) . " controllers\n\n";

// Step 2: Find ALL PHP files with controller references
$allFiles = [];
foreach ($searchDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $allFiles[] = $file->getPathname();
        }
    }
}

echo "📁 Found " . count($allFiles) . " PHP files to scan\n\n";

// Step 3: Fix all files
foreach ($allFiles as $routeFile) {
    if (!file_exists($routeFile)) {
        echo "⚠️  Route file not found: {$routeFile}\n";
        continue;
    }
    
    echo "🔍 Processing: " . basename($routeFile) . "\n";
    $content = file_get_contents($routeFile);
    $originalContent = $content;
    $fixes = 0;
    
    // Find all controller references (use statements, route definitions, etc.)
    $patterns = [
        '/use\s+PterodactylAddons\\\\ShopSystem\\\\Http\\\\Controllers\\\\([^;]+);/',
        '/PterodactylAddons\\\\ShopSystem\\\\Http\\\\Controllers\\\\([A-Za-z\\\\]+)/',
        '/\'([A-Za-z]+Controller)\'/',
        '/"([A-Za-z]+Controller)"/'
    ];
    
    $allMatches = [];
    foreach ($patterns as $pattern) {
        preg_match_all($pattern, $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $match) {
                $allMatches[] = [
                    'full' => $matches[0][$index],
                    'controller' => $match
                ];
            }
        }
    }
    
    foreach ($allMatches as $match) {
        $controllerPath = $match['controller'];
        $fullUse = $useMatches[0][$index];
        $parts = explode('\\', $controllerPath);
        $className = end($parts);
        
        echo "  🔍 Checking: {$controllerPath}\n";
        
        // Check if this exact controller exists
        $found = false;
        foreach ($existingControllers as $existingPath => $filePath) {
            if (str_ends_with($existingPath, $controllerPath)) {
                echo "    ✓ Exact match found\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            // Try to find a similar controller
            $bestMatch = null;
            $bestScore = 0;
            
            foreach ($existingControllers as $existingPath => $filePath) {
                $existingParts = explode('\\', $existingPath);
                $existingClassName = end($existingParts);
                
                // Check for similar names
                if (strpos($existingClassName, $className) !== false || 
                    strpos($className, $existingClassName) !== false) {
                    
                    $score = similar_text($className, $existingClassName);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestMatch = $existingPath;
                    }
                }
            }
            
            if ($bestMatch) {
                echo "    🔄 Suggested fix: {$controllerPath} → {$bestMatch}\n";
                
                // Replace the use statement
                $newUse = "use PterodactylAddons\\ShopSystem\\Http\\Controllers\\{$bestMatch};";
                $content = str_replace($fullUse, $newUse, $content);
                
                // Replace controller references in routes
                $shortName = end(explode('\\', $controllerPath));
                $newShortName = end(explode('\\', $bestMatch));
                
                // Replace [ControllerName::class, 'method'] patterns
                $content = preg_replace(
                    '/\[' . preg_quote($shortName, '/') . '::class,/',
                    '[' . $newShortName . '::class,',
                    $content
                );
                
                $fixes++;
            } else {
                echo "    ❌ No suitable replacement found for: {$controllerPath}\n";
            }
        }
    }
    
    // Save changes if any were made
    if ($content !== $originalContent) {
        file_put_contents($routeFile, $content);
        echo "  ✅ Applied {$fixes} fixes to " . basename($routeFile) . "\n";
    } else {
        echo "  ℹ️  No changes needed for " . basename($routeFile) . "\n";
    }
    
    echo "\n";
}

// Step 3: Test route loading
echo "🧪 Testing route loading...\n";
$testCommand = 'cd /var/www/pterodactyl && php artisan route:list 2>&1';
$output = shell_exec($testCommand);

if (strpos($output, 'Error') !== false || strpos($output, 'Exception') !== false) {
    echo "❌ Route loading failed:\n";
    echo $output . "\n";
} else {
    // Count shop routes
    $shopRouteCount = shell_exec('cd /var/www/pterodactyl && php artisan route:list | grep -E "ShopSystem|PterodactylAddons" | wc -l');
    $shopRouteCount = intval(trim($shopRouteCount));
    
    echo "✅ Routes loaded successfully!\n";
    echo "📊 Shop routes found: {$shopRouteCount}\n";
    
    if ($shopRouteCount > 10) {
        echo "🎉 SUCCESS: Route loading appears to be working!\n";
    } else {
        echo "⚠️  WARNING: Only {$shopRouteCount} routes found. Expected more.\n";
    }
}

echo "\n🏁 Route fix script completed!\n";
