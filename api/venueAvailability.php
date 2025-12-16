<?php
require_once "../config/database.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$venue_id = $_GET['venue_id'] ?? null;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

if (!$venue_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Venue ID is required']);
    exit;
}

try {
    global $pdo;
    
    // If no date range provided, get availability for next 30 days
    if (!$start_date) {
        $start_date = date('Y-m-d');
    }
    if (!$end_date) {
        $end_date = date('Y-m-d', strtotime('+30 days'));
    }
    
    // Get all bookings for this venue in the date range
    $stmt = $pdo->prepare("
        SELECT booking_date, status, COUNT(*) as count
        FROM bookings
        WHERE venue_id = ?
        AND booking_date BETWEEN ? AND ?
        AND status IN ('confirmed', 'pending')
        GROUP BY booking_date, status
    ");
    
    $stmt->execute([$venue_id, $start_date, $end_date]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create availability map
    $availability_map = [];
    foreach ($bookings as $booking) {
        $date = $booking['booking_date'];
        $count = (int)$booking['count'];
        $status = $booking['status'];
        
        if (!isset($availability_map[$date])) {
            $availability_map[$date] = ['total_bookings' => 0, 'confirmed' => 0, 'pending' => 0];
        }
        
        $availability_map[$date]['total_bookings'] += $count;
        if ($status === 'confirmed') {
            $availability_map[$date]['confirmed'] += $count;
        } else {
            $availability_map[$date]['pending'] += $count;
        }
    }
    
    // Generate date range and mark availability
    $date_availability = [];
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    while ($current_date <= $end_date_obj) {
        $date_str = $current_date->format('Y-m-d');
        $bookings_info = $availability_map[$date_str] ?? ['total_bookings' => 0, 'confirmed' => 0, 'pending' => 0];
        
        // For now, any booking makes the venue unavailable
        // In the future, this could be enhanced to support multiple bookings per day
        $is_available = $bookings_info['total_bookings'] === 0;
        
        $date_availability[] = [
            'date' => $date_str,
            'available' => $is_available,
            'bookings' => $bookings_info['total_bookings'],
            'confirmed_bookings' => $bookings_info['confirmed'],
            'pending_bookings' => $bookings_info['pending'],
            'status' => $is_available ? 'available' : 'unavailable'
        ];
        
        $current_date->modify('+1 day');
    }
    
    // Get venue basic info
    $venue_stmt = $pdo->prepare("SELECT name FROM venues WHERE id = ?");
    $venue_stmt->execute([$venue_id]);
    $venue = $venue_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'venue_id' => $venue_id,
        'venue_name' => $venue['name'] ?? 'Unknown Venue',
        'date_range' => [
            'start_date' => $start_date,
            'end_date' => $end_date
        ],
        'availability' => $date_availability,
        'summary' => [
            'total_dates' => count($date_availability),
            'available_dates' => count(array_filter($date_availability, function($d) { return $d['available']; })),
            'unavailable_dates' => count(array_filter($date_availability, function($d) { return !$d['available']; }))
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>