<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/config.php';
require_once '../config/database.php';

// Start session and check authentication
session_start();
$user = $_SESSION['user'] ?? null;
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['orderID']) || empty($input['orderID'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID is required']);
        exit;
    }
    
    $orderID = $input['orderID'];
    
    // PayPal API configuration
    $clientId = env('PAYPAL_CLIENT_ID');
    $clientSecret = env('PAYPAL_SECRET');
    $mode = env('PAYPAL_MODE', 'sandbox');
    
    if (empty($clientId) || empty($clientSecret)) {
        http_response_code(500);
        echo json_encode(['error' => 'PayPal credentials not configured']);
        exit;
    }
    
    // PayPal API endpoints
    $baseUrl = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    
    // Get PayPal access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US',
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('PayPal API request failed: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('PayPal authentication failed');
    }
    
    $authData = json_decode($response, true);
    $accessToken = $authData['access_token'] ?? null;
    
    if (!$accessToken) {
        throw new Exception('Failed to obtain PayPal access token');
    }
    
    // Verify the order first
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v2/checkout/orders/' . $orderID);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('PayPal order verification failed: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to verify PayPal order');
    }
    
    $orderData = json_decode($response, true);
    
    // Check if order is approved
    if ($orderData['status'] !== 'APPROVED') {
        http_response_code(400);
        echo json_encode(['error' => 'Order not approved']);
        exit;
    }
    
    // Capture the payment
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v2/checkout/orders/' . $orderID . '/capture');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'PayPal-Request-Id: ' . uniqid()
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('PayPal payment capture failed: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 201) {
        $errorData = json_decode($response, true);
        throw new Exception('PayPal payment capture failed: ' . ($errorData['message'] ?? 'Unknown error'));
    }
    
    $captureData = json_decode($response, true);
    
    // Extract capture information
    $purchaseUnit = $captureData['purchase_units'][0] ?? null;
    $payment = $purchaseUnit['payments']['captures'][0] ?? null;
    
    if (!$payment) {
        throw new Exception('Payment capture information not found');
    }
    
    $captureID = $payment['id'];
    $status = $payment['status'];
    $amount = $payment['amount']['value'];
    $currency = $payment['amount']['currency_code'];
    
    // Verify payment status
    if ($status !== 'COMPLETED') {
        http_response_code(400);
        echo json_encode(['error' => 'Payment not completed', 'status' => $status]);
        exit;
    }
    
    // Check if booking data was provided for final verification
    if (isset($input['booking_data'])) {
        $bookingData = $input['booking_data'];
        $expectedAmount = $bookingData['total_amount'] ?? 0;
        
        // Verify the amount matches (with tolerance for rounding)
        if (abs(floatval($amount) - floatval($expectedAmount)) > 0.01) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Payment amount mismatch',
                'expected' => $expectedAmount,
                'received' => $amount
            ]);
            exit;
        }
    }
    
    // Return success response with capture details
    echo json_encode([
        'success' => true,
        'captureID' => $captureID,
        'orderID' => $orderID,
        'amount' => $amount,
        'currency' => $currency,
        'status' => $status,
        'payment_method' => 'PAYPAL',
        'message' => 'Payment captured successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Payment capture failed',
        'message' => $e->getMessage()
    ]);
}
?>