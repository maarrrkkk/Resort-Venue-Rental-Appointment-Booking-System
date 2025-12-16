<?php
require_once "../config/database.php";

header('Content-Type: application/json');

// Only allow POST requests for creating bookings
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['venue_id', 'user_id', 'booking_date', 'guest_count', 'event_type'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }
    
    $venue_id = $input['venue_id'];
    $user_id = $input['user_id'];
    $booking_date = $input['booking_date'];
    $guest_count = (int)$input['guest_count'];
    $event_type = $input['event_type'];
    $special_requests = $input['special_requests'] ?? '';
    $total_amount = (float)($input['total_amount'] ?? 0);
    
    global $pdo;
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Double-check venue availability
    $availability_check = $pdo->prepare("
        SELECT COUNT(*) as booking_count
        FROM bookings
        WHERE venue_id = ?
        AND booking_date = ?
        AND status IN ('confirmed', 'pending')
    ");
    $availability_check->execute([$venue_id, $booking_date]);
    $availability_result = $availability_check->fetch(PDO::FETCH_ASSOC);
    
    if ($availability_result['booking_count'] > 0) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            'error' => 'Venue is not available on the selected date',
            'code' => 'DATE_UNAVAILABLE',
            'booking_count' => (int)$availability_result['booking_count']
        ]);
        exit;
    }
    
    // Get venue details
    $venue_stmt = $pdo->prepare("SELECT name, capacity FROM venues WHERE id = ?");
    $venue_stmt->execute([$venue_id]);
    $venue = $venue_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venue) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Venue not found']);
        exit;
    }
    
    // Verify guest count doesn't exceed capacity
    if ($guest_count > $venue['capacity']) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'error' => 'Guest count exceeds venue capacity',
            'capacity' => $venue['capacity'],
            'requested_guests' => $guest_count
        ]);
        exit;
    }
    
    // Create booking
    $booking_id = uniqid('booking_');
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            id, user_id, venue_id, booking_date, start_time, end_time, duration,
            guest_count, event_type, special_requests, total_amount,
            payment_method, payment_type, payment_status, status
        ) VALUES (?, ?, ?, ?, '08:00:00', '17:00:00', 9, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
    ");
    
    $stmt->execute([
        $booking_id,
        $user_id,
        $venue_id,
        $booking_date,
        $guest_count,
        $event_type,
        $special_requests,
        $total_amount,
        $input['payment_method'] ?? 'pending',
        $input['payment_type'] ?? 'pending'
    ]);
    
    // Log the booking creation
    error_log("Booking created successfully: $booking_id for venue $venue_id on $booking_date");
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'message' => 'Booking created successfully',
        'booking' => [
            'id' => $booking_id,
            'venue_name' => $venue['name'],
            'booking_date' => $booking_date,
            'guest_count' => $guest_count,
            'event_type' => $event_type,
            'total_amount' => $total_amount,
            'status' => 'pending'
        ]
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    // Check for duplicate key error (race condition)
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode([
            'error' => 'This venue is no longer available for the selected date',
            'code' => 'RACE_CONDITION'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    
    error_log("Booking creation failed: " . $e->getMessage());
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    
    error_log("Booking creation failed: " . $e->getMessage());
}
?>