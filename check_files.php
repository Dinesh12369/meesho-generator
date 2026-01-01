<?php
// File verification script
header('Content-Type: text/plain');

echo "=== File Verification ===\n\n";

$required_files = [
    'config.php',
    'index.php',
    'script.js',
    'api_search_categories.php',
    'api_auto_login.php',
    'api_generate.php',
    'viewer.php',
    'download.php',
    'reset_credits.php'
];

echo "Checking required files:\n\n";

foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✓ $file - EXISTS (" . filesize($path) . " bytes)\n";
    } else {
        echo "✗ $file - MISSING\n";
    }
}

echo "\n=== Directory Contents ===\n\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "  - $file\n";
    }
}

echo "\n=== PHP Info ===\n\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "cURL: " . (function_exists('curl_init') ? 'ENABLED' : 'DISABLED') . "\n";
echo "GD: " . (extension_loaded('gd') ? 'ENABLED' : 'DISABLED') . "\n";
echo "Session: " . (function_exists('session_start') ? 'ENABLED' : 'DISABLED') . "\n";

echo "\n=== Working Directory ===\n";
echo "Current: " . getcwd() . "\n";
echo "__DIR__: " . __DIR__ . "\n";
?>
