<?php
/**
 * Debug script to test venue loading
 * Place this file in the root directory and access it via browser
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo json_encode([
    'status' => 'debug',
    'message' => 'Testing venue loading...',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
]);

// Test 1: Check if config files exist
$tests = [];
$tests['config_exists'] = file_exists(__DIR__ . '/config/database.php');
$tests['includes_config_exists'] = file_exists(__DIR__ . '/includes/config.php');
$tests['env_exists'] = file_exists(__DIR__ . '/.env');
$tests['env_example_exists'] = file_exists(__DIR__ . '/.env.example');

// Test 2: Try to include config
try {
    require_once __DIR__ . '/includes/config.php';
    $tests['config_included'] = true;
    $tests['db_host'] = env('DB_HOST', 'not set');
    $tests['db_name'] = env('DB_NAME', 'not set');
    $tests['db_user'] = env('DB_USER', 'not set');
} catch (Exception $e) {
    $tests['config_included'] = false;
    $tests['config_error'] = $e->getMessage();
}

// Test 3: Try database connection
try {
    require_once __DIR__ . '/config/database.php';
    $tests['database_connected'] = true;
    
    // Test 4: Check if venues table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'venues'");
    $tests['venues_table_exists'] = $stmt->rowCount() > 0;
    
    if ($tests['venues_table_exists']) {
        // Test 5: Try to fetch venues
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM venues");
        $result = $stmt->fetch();
        $tests['venues_count'] = $result['count'];
        
        // Test 6: Try to fetch actual venues
        $stmt = $pdo->query("SELECT * FROM venues LIMIT 3");
        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $tests['sample_venues'] = $venues;
    }
    
} catch (Exception $e) {
    $tests['database_connected'] = false;
    $tests['database_error'] = $e->getMessage();
}

// Test 7: Check API file
$tests['api_venues_exists'] = file_exists(__DIR__ . '/api/venues.php');

echo json_encode([
    'status' => 'debug_complete',
    'tests' => $tests,
    'php_version' => phpversion(),
    'current_dir' => __DIR__,
    'server_info' => $_SERVER
], JSON_PRETTY_PRINT);