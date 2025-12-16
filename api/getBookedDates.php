<?php
require_once "../config/database.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$venue_id = $_GET['venue_id'] ?? null;

if (!$venue_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Venue ID is required']);
    exit;
}

try {
    global $pdo;
    
    // Get all booked dates for this venue (confirmed or pending bookings)
    // Only get dates from today onwards
    $stmt = $pdo->prepare("
        SELECT DISTINCT booking_date
        FROM bookings
        WHERE venue_id = ?
        AND booking_date >= CURDATE()
        AND status IN ('confirmed', 'pending')
        ORDER BY booking_date ASC
    ");
    
    $stmt->execute([$venue_id]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'venue_id' => $venue_id,
        'booked_dates' => $results
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
