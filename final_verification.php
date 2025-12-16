<?php
/**
 * Final verification that the fix is working
 */

require_once 'config/database.php';

echo "=== FINAL VERIFICATION ===\n\n";

// Check Executive Conference Center status
$stmt = $pdo->query('
    SELECT v.name,
           CASE 
               WHEN EXISTS (
                   SELECT 1 FROM bookings b 
                   WHERE b.venue_id = v.id 
                   AND b.booking_date = CURDATE() 
                   AND b.status IN ("confirmed", "pending")
               ) THEN "booked_today"
               ELSE "available"
           END as status
    FROM venues v 
    WHERE v.name LIKE "%Executive Conference%"
');

$result = $stmt->fetch();

if ($result) {
    echo "Venue: {$result['name']}\n";
    echo "Status: {$result['status']}\n";
    
    if ($result['status'] === 'booked_today') {
        echo "\n✅ SUCCESS: The fix is working!\n";
        echo "✅ Executive Conference Center should now show:\n";
        echo "   🔴 Red 'Booked Today' badge\n";
        echo "   🟫 Gray 'Booked Today' button (disabled)\n\n";
    } else {
        echo "\n❌ FAILED: Status should be 'booked_today'\n";
    }
} else {
    echo "❌ No result found\n";
}

// Verify today's bookings
$today = date('Y-m-d');
$stmt = $pdo->query("
    SELECT v.name, COUNT(*) as booking_count
    FROM bookings b
    JOIN venues v ON b.venue_id = v.id
    WHERE b.booking_date = '$today'
    AND b.status IN ('confirmed', 'pending')
    GROUP BY v.id, v.name
");

$todayBookings = $stmt->fetchAll();

echo "Today's bookings (" . count($todayBookings) . " total):\n";
foreach ($todayBookings as $booking) {
    echo "  📌 {$booking['name']}: {$booking['booking_count']} booking(s)\n";
}

echo "\n🎯 NEXT STEP: Visit venue.php to see the fix in action!\n";
echo "URL: index.php?page=venue\n";
?>