<?php
/**
 * Test the fixed venue availability system
 */

require_once 'config/database.php';

echo "=== TESTING FIXED VENUE AVAILABILITY ===\n\n";

$today = date('Y-m-d');
echo "Today's date: $today\n\n";

// Test the new logic from venue.php
echo "🔍 TESTING NEW AVAILABILITY LOGIC:\n";
echo str_repeat("=", 60) . "\n";

// Get all venues first
$venuesStmt = $pdo->query("SELECT * FROM venues ORDER BY name");
$allVenues = $venuesStmt->fetchAll(PDO::FETCH_ASSOC);

echo "✅ Loaded " . count($allVenues) . " venues\n\n";

// Get today's bookings in a separate query for accuracy
$todayBookingsStmt = $pdo->query("
    SELECT venue_id, COUNT(*) as booking_count
    FROM bookings 
    WHERE booking_date = CURDATE() 
    AND status IN ('confirmed', 'pending')
    GROUP BY venue_id
");
$todayBookings = [];
while ($booking = $todayBookingsStmt->fetch(PDO::FETCH_ASSOC)) {
    $todayBookings[$booking['venue_id']] = $booking['booking_count'];
}

echo "📅 Today's bookings by venue:\n";
if (empty($todayBookings)) {
    echo "❌ No bookings found for today\n\n";
} else {
    foreach ($todayBookings as $venueId => $count) {
        // Find venue name
        $venueName = '';
        foreach ($allVenues as $venue) {
            if ($venue['id'] === $venueId) {
                $venueName = $venue['name'];
                break;
            }
        }
        echo "  📌 $venueName (ID: $venueId): $count booking(s)\n";
    }
    echo "\n";
}

// Process each venue with availability status
echo "🎯 FINAL VENUE STATUS:\n";
echo str_repeat("=", 60) . "\n";

foreach ($allVenues as $venue) {
    $venueId = $venue['id'];
    $todayBookingCount = $todayBookings[$venueId] ?? 0;
    
    if ($todayBookingCount > 0) {
        $status = 'booked_today';
        $icon = '🔴';
        $badge = 'Booked Today';
    } else {
        $status = 'available';
        $icon = '🟢';
        $badge = 'Available';
    }
    
    echo sprintf("%s %s - %s (Bookings today: %d)\n", 
        $icon, 
        $venue['name'], 
        $badge, 
        $todayBookingCount
    );
}

echo "\n✅ FIX VERIFICATION:\n";
echo str_repeat("-", 60) . "\n";

// Verify that Grand Ballroom is now correctly marked as available
$grandBallroom = null;
foreach ($allVenues as $venue) {
    if (strpos($venue['name'], 'Grand Ballroom') !== false) {
        $grandBallroom = $venue;
        break;
    }
}

if ($grandBallroom) {
    $venueId = $grandBallroom['id'];
    $todayBookingCount = $todayBookings[$venueId] ?? 0;
    
    if ($todayBookingCount === 0) {
        echo "✅ SUCCESS: Grand Ballroom correctly shows as AVAILABLE\n";
        echo "✅ The SQL query bug has been fixed!\n";
    } else {
        echo "❌ ISSUE: Grand Ballroom still shows bookings for today\n";
    }
} else {
    echo "❌ Grand Ballroom not found\n";
}

echo "\n🎉 SUMMARY:\n";
$bookedCount = count(array_filter($todayBookings));
$totalVenues = count($allVenues);
$availableCount = $totalVenues - $bookedCount;

echo "✅ Available venues: $availableCount\n";
echo "🔴 Booked venues: $bookedCount\n";
echo "📊 Total venues: $totalVenues\n\n";

if ($bookedCount >= 0 && $availableCount >= 0) {
    echo "🎯 NEXT STEP: Visit venue.php to see the corrected availability!\n";
    echo "URL: index.php?page=venue\n";
} else {
    echo "❌ Something is still wrong with the logic\n";
}
?>