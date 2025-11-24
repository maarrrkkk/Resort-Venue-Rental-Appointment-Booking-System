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
    $requiredFields = ['venue_id', 'venue_price', 'guest_count', 'booking_date'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }
    
    // Extract booking data
    $venueId = $input['venue_id'];
    $venuePrice = (float)$input['venue_price'];
    $guestCount = (int)$input['guest_count'];
    $bookingDate = $input['booking_date'];
    $eventType = $input['event_type'] ?? '';
    $specialRequests = $input['special_requests'] ?? '';
    
    // Get venue capacity and calculate total cost
    $stmt = $pdo->prepare("SELECT capacity FROM venues WHERE id = ?");
    $stmt->execute([$venueId]);
    $venue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venue) {
        http_response_code(404);
        echo json_encode(['error' => 'Venue not found']);
        exit;
    }
    
    $venueCapacity = (int)$venue['capacity'];
    $pricePerGuest = 50; // Fixed price per guest
    $extraGuests = max(0, $guestCount - $venueCapacity);
    $totalAmount = $venuePrice + ($extraGuests * $pricePerGuest);
    
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
    
    // Create PayPal order
    $orderData = [
        'intent' => 'CAPTURE',
        'application_context' => [
            'return_url' => env('BASE_URL') . '/index.php?page=booking&payment=completed',
            'cancel_url' => env('BASE_URL') . '/index.php?page=booking&payment=cancel',
            'brand_name' => env('SITE_NAME', 'Resort Venue Booking'),
            'locale' => 'en-US',
            'landing_page' => 'BILLING',
            'user_action' => 'PAY_NOW',
            'shipping_preference' => 'NO_SHIPPING'
        ],
        'purchase_units' => [
            [
                'reference_id' => 'BOOKING_' . uniqid(),
                'description' => "Venue booking for {$bookingDate}",
                'custom_id' => "venue_{$venueId}_date_{$bookingDate}",
                'amount' => [
                    'currency_code' => 'PHP',
                    'value' => number_format($totalAmount, 2, '.', ''),
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => 'PHP',
                            'value' => number_format($totalAmount, 2, '.', '')
                        ]
                    ]
                ],
                'items' => [
                    [
                        'name' => 'Venue Rental',
                        'description' => "Venue booking on {$bookingDate}",
                        'sku' => "venue_{$venueId}",
                        'unit_amount' => [
                            'currency_code' => 'PHP',
                            'value' => number_format($totalAmount, 2, '.', '')
                        ],
                        'quantity' => '1',
                        'category' => 'DIGITAL_GOODS'
                    ]
                ]
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'PayPal-Request-Id: ' . uniqid()
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('PayPal order creation failed: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 201) {
        $errorData = json_decode($response, true);
        throw new Exception('PayPal order creation failed: ' . ($errorData['message'] ?? 'Unknown error'));
    }
    
    $orderData = json_decode($response, true);
    
    // Return the order ID and approval URL
    $approveUrl = null;
    foreach ($orderData['links'] as $link) {
        if ($link['rel'] === 'approve') {
            $approveUrl = $link['href'];
            break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'orderID' => $orderData['id'],
        'approveUrl' => $approveUrl,
        'total_amount' => $totalAmount,
        'currency' => 'PHP'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to create PayPal order',
        'message' => $e->getMessage()
    ]);
}
?>