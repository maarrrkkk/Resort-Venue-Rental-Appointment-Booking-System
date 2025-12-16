<?php
require_once "../config/database.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$venue_id = $_GET['venue_id'] ?? null;
$booking_date = $_GET['date'] ?? null;

if (!$venue_id || !$booking_date) {
    http_response_code(400);
    echo json_encode(['error' => 'Venue ID and date are required']);
    exit;
}

try {
    global $pdo;
    
    // Check if there are any confirmed bookings for this venue on this date
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as booking_count,
               GROUP_CONCAT(CONCAT('Booking #', SUBSTRING(id, 9)) SEPARATOR ', ') as existing_bookings
        FROM bookings
        WHERE venue_id = ?
        AND booking_date = ?
        AND status IN ('confirmed', 'pending')
    ");
    
    $stmt->execute([$venue_id, $booking_date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $is_available = ($result['booking_count'] == 0);
    
    echo json_encode([
        'available' => $is_available,
        'venue_id' => $venue_id,
        'date' => $booking_date,
        'booking_count' => (int)$result['booking_count'],
        'existing_bookings' => $result['existing_bookings'] ?? '',
        'message' => $is_available ?
            'Venue is available on this date' :
            'Venue is not available - conflicts with: ' . ($result['existing_bookings'] ?? 'existing booking')
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>