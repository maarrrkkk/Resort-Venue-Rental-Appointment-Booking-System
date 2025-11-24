<?php
/**
 * API endpoint to provide PayPal Client ID to frontend
 * This allows dynamic loading of PayPal credentials without exposing them in client-side code
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once '../includes/config.php';

try {
    // Get PayPal configuration from environment
    $clientId = env('PAYPAL_CLIENT_ID');
    $mode = env('PAYPAL_MODE', 'sandbox');
    
    if (empty($clientId) || $clientId === 'your-paypal-client-id-here') {
        http_response_code(500);
        echo json_encode([
            'error' => 'PayPal not configured',
            'message' => 'Please configure PAYPAL_CLIENT_ID in your .env file'
        ]);
        exit;
    }
    
    // Return PayPal configuration
    echo json_encode([
        'success' => true,
        'clientId' => $clientId,
        'mode' => $mode,
        'environment' => $mode === 'live' ? 'production' : 'sandbox',
        'currency' => 'PHP',
        'intent' => 'capture'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to get PayPal configuration',
        'message' => $e->getMessage()
    ]);
}
?>