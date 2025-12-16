<?php
/**
 * Quick check for today's bookings
 */

require_once 'config/database.php';

echo "=== QUICK BOOKING CHECK ===\n\n";

$today = date('Y-m-d');
echo "Today's date: $today\n\n";

// Find Executive Conference Center
$stmt = $pdo->prepare("SELECT id, name FROM venues WHERE name LIKE '%Conference%' LIMIT 1");
$stmt->execute();
$venue = $stmt->fetch();

if (!$venue) {
    echo "❌ Executive Conference Center not found\n";
    exit;
}

echo "✅ Found venue: {$venue['name']} (ID: {$venue['id']})\n\n";

// Check bookings for this venue today
$stmt = $pdo->prepare("
    SELECT * FROM bookings 
    WHERE venue_id = ? 
    AND booking_date = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$venue['id'], $today]);
$bookings = $stmt->fetchAll();

echo "Bookings for {$venue['name']} on $today:\n";
if (empty($bookings)) {
    echo "❌ NO BOOKINGS FOUND - This explains why the venue shows as available!\n\n";
    
    // Check if there are any bookings at all for this venue
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE venue_id = ?");
    $stmt->execute([$venue['id']]);
    $totalBookings = $stmt->fetch()['count'];
    echo "Total bookings for this venue (all dates): $totalBookings\n";
    
} else {
    echo "✅ Found " . count($bookings) . " booking(s):\n";
    foreach ($bookings as $booking) {
        echo "  - ID: {$booking['id']}, Status: {$booking['status']}, Created: {$booking['created_at']}\n";
    }
}

echo "\n=== CONCLUSION ===\n";
if (empty($bookings)) {
    echo "❌ ISSUE: No booking was created for today\n";
    echo "💡 SOLUTION: The booking process failed - check the booking form and payment process\n";
} else {
    echo "✅ Booking exists but availability query may have issues\n";
    echo "💡 SOLUTION: Check the venue.php availability query logic\n";
}
?>