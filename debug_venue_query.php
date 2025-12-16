<?php
/**
 * Debug the availability query issue
 */

require_once 'config/database.php';

echo "=== DEBUGGING VENUE AVAILABILITY QUERY ===\n\n";

$today = date('Y-m-d');
echo "Today's date: $today\n\n";

// Check each venue individually
echo "🔍 DETAILED VENUE CHECK:\n";
echo str_repeat("=", 80) . "\n";

$venues = $pdo->query("SELECT id, name FROM venues ORDER BY name")->fetchAll();

foreach ($venues as $venue) {
    echo "\n📍 VENUE: {$venue['name']} (ID: {$venue['id']})\n";
    echo str_repeat("-", 50) . "\n";
    
    // Check bookings for this specific venue today
    $stmt = $pdo->prepare("
        SELECT * FROM bookings 
        WHERE venue_id = ? 
        AND booking_date = ? 
        AND status IN ('confirmed', 'pending')
    ");
    $stmt->execute([$venue['id'], $today]);
    $venueBookings = $stmt->fetchAll();
    
    echo "Bookings today: " . count($venueBookings) . "\n";
    
    if (!empty($venueBookings)) {
        foreach ($venueBookings as $booking) {
            echo "  - ID: {$booking['id']}\n";
            echo "    Date: {$booking['booking_date']}\n";
            echo "    Status: {$booking['status']}\n";
        }
    } else {
        echo "  ❌ No bookings found for today\n";
    }
    
    // Test the exact query used in venue.php
    $query = "
        SELECT 
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM bookings b 
                    WHERE b.venue_id = v.id 
                    AND b.booking_date = CURDATE() 
                    AND b.status IN ('confirmed', 'pending')
                ) THEN 'booked_today'
                ELSE 'available'
            END as status
        FROM venues v 
        WHERE v.id = '{$venue['id']}'
    ";
    
    $result = $pdo->query($query)->fetch();
    echo "Query result: " . $result['status'] . "\n";
    
    // Manual check
    $manualCheck = count($venueBookings) > 0 ? 'booked_today' : 'available';
    echo "Manual check: $manualCheck\n";
    
    if ($result['status'] !== $manualCheck) {
        echo "❌ MISMATCH DETECTED!\n";
    } else {
        echo "✅ Query matches manual check\n";
    }
}

echo "\n🔍 TESTING THE CURRENT QUERY:\n";
echo str_repeat("=", 80) . "\n";

$testQuery = "
    SELECT v.id, v.name,
           CASE 
               WHEN EXISTS (
                   SELECT 1 FROM bookings b 
                   WHERE b.venue_id = v.id 
                   AND b.booking_date = CURDATE() 
                   AND b.status IN ('confirmed', 'pending')
               ) THEN 'booked_today'
               ELSE 'available'
           END as availability_status,
           (
               SELECT COUNT(*) 
               FROM bookings b 
               WHERE b.venue_id = v.id 
               AND b.booking_date = CURDATE() 
               AND b.status IN ('confirmed', 'pending')
           ) as today_bookings
    FROM venues v
    ORDER BY v.name
";

$testResults = $pdo->query($testQuery)->fetchAll();

echo "Query Results:\n";
foreach ($testResults as $result) {
    $expected = $result['today_bookings'] > 0 ? 'booked_today' : 'available';
    $status = $result['availability_status'];
    $match = $expected === $status ? '✅' : '❌';
    
    echo sprintf("%s %s: Query=%s, Expected=%s, Count=%d\n", 
        $match,
        $result['name'], 
        $status, 
        $expected, 
        $result['today_bookings']
    );
}
?>