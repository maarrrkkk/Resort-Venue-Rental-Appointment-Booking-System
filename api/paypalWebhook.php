<?php
/**
 * PayPal Webhook Handler for Advanced Payment Verification
 * This provides an additional layer of security by verifying payments server-to-server
 * 
 * To use this webhook:
 * 1. Configure webhook URL in your PayPal developer dashboard: https://developer.paypal.com/developer/applications/
 * 2. Set the webhook URL to: https://yourdomain.com/api/paypalWebhook.php
 * 3. Subscribe to PAYMENT.CAPTURE.COMPLETED and PAYMENT.CAPTURE.REFUNDED events
 */

header('Content-Type: application/json');

// PayPal sends webhook notifications via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

require_once '../includes/config.php';
require_once '../config/database.php';

try {
    // Get the raw POST data
    $payload = file_get_contents('php://input');
    $headers = getallheaders();
    
    // Validate webhook signature (PayPal verification)
    $transmissionId = $headers['Transmission-Id'] ?? '';
    $transmissionTime = $headers['Transmission-Time'] ?? '';
    $certUrl = $headers['Cert-Url'] ?? '';
    $authAlgo = $headers['Auth-Algo'] ?? '';
    $transmissionSig = $headers['Transmission-Sig'] ?? '';
    $webhookId = env('PAYPAL_WEBHOOK_ID'); // Set this in your PayPal app settings
    
    if (empty($transmissionId) || empty($webhookId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid webhook configuration']);
        exit;
    }
    
    // PayPal API configuration
    $clientId = env('PAYPAL_CLIENT_ID');
    $clientSecret = env('PAYPAL_SECRET');
    $mode = env('PAYPAL_MODE', 'sandbox');
    
    if (empty($clientId) || empty($clientSecret)) {
        throw new Exception('PayPal credentials not configured');
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
    
    // Verify webhook transmission
    $verifyData = [
        'transmission_id' => $transmissionId,
        'transmission_time' => $transmissionTime,
        'cert_url' => $certUrl,
        'auth_algo' => $authAlgo,
        'transmission_sig' => $transmissionSig,
        'webhook_id' => $webhookId,
        'webhook_event' => json_decode($payload, true)
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/notifications/verify-webhook-signature');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verifyData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('Webhook verification failed: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Webhook verification request failed');
    }
    
    $verificationData = json_decode($response, true);
    
    // Check if webhook is valid
    if ($verificationData['verification_status'] !== 'SUCCESS') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid webhook signature']);
        exit;
    }
    
    // Process the webhook event
    $event = json_decode($payload, true);
    $eventType = $event['event_type'] ?? '';
    
    // Handle different webhook events
    switch ($eventType) {
        case 'PAYMENT.CAPTURE.COMPLETED':
            handlePaymentCompleted($event, $pdo);
            break;
            
        case 'PAYMENT.CAPTURE.REFUNDED':
            handlePaymentRefunded($event, $pdo);
            break;
            
        default:
            // Log unhandled events for debugging
            error_log("Unhandled PayPal webhook event: " . $eventType);
    }
    
    echo json_encode(['success' => true, 'message' => 'Webhook processed successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Webhook processing failed',
        'message' => $e->getMessage()
    ]);
}

/**
 * Handle payment completed webhook
 */
function handlePaymentCompleted($event, $pdo) {
    $resource = $event['resource'] ?? [];
    $captureId = $resource['id'] ?? '';
    $amount = $resource['amount']['value'] ?? '';
    $currency = $resource['amount']['currency_code'] ?? '';
    
    if (empty($captureId)) {
        throw new Exception('Capture ID not found in webhook event');
    }
    
    // Find booking by PayPal capture ID
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE paypal_capture_id = ?");
    $stmt->execute([$captureId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        // Update booking status to PAID
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET payment_status = 'PAID',
                status = 'confirmed'
            WHERE paypal_capture_id = ?
        ");
        $stmt->execute([$captureId]);
        
        // Log the webhook processing
        error_log("PayPal webhook: Payment completed for booking " . $booking['id']);
    } else {
        // Log when booking is not found
        error_log("PayPal webhook: Booking not found for capture ID " . $captureId);
    }
}

/**
 * Handle payment refunded webhook
 */
function handlePaymentRefunded($event, $pdo) {
    $resource = $event['resource'] ?? [];
    $captureId = $resource['id'] ?? '';
    
    if (empty($captureId)) {
        throw new Exception('Capture ID not found in refund webhook event');
    }
    
    // Find booking by PayPal capture ID
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE paypal_capture_id = ?");
    $stmt->execute([$captureId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        // Update booking status to REFUNDED
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET payment_status = 'REFUNDED',
                status = 'cancelled'
            WHERE paypal_capture_id = ?
        ");
        $stmt->execute([$captureId]);
        
        // Log the webhook processing
        error_log("PayPal webhook: Payment refunded for booking " . $booking['id']);
    }
}
?>