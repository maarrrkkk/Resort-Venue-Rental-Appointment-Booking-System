<?php
/**
 * Test the fixed venue page with the corrected availability logic
 */

require_once 'config/database.php';

echo "=== TESTING FIXED VENUE PAGE LOGIC ===\n\n";

$today = date('Y-m-d');
echo "Today's date: $today\n\n";

// Get all venues
$venuesStmt = $pdo->query("SELECT * FROM venues ORDER BY name");
$allVenues = $venuesStmt->fetchAll(PDO::FETCH_ASSOC);

echo "✅ Loaded " . count($allVenues) . " venues\n\n";

// Get today's bookings using the same logic as venue.php
$todayBookingsStmt = $pdo->prepare("
    SELECT venue_id, COUNT(*) as booking_count
    FROM bookings 
    WHERE booking_date = ? 
    AND status IN ('confirmed', 'pending')
    GROUP BY venue_id
");
$todayBookingsStmt->execute([$today]);
$todayBookingsMap = [];
while ($booking = $todayBookingsStmt->fetch(PDO::FETCH_ASSOC)) {
    $todayBookingsMap[$booking['venue_id']] = $booking['booking_count'];
}

// Add availability status to each venue
foreach ($allVenues as &$venue) {
    $venueId = $venue['id'];
    $todayBookingCount = $todayBookingsMap[$venueId] ?? 0;
    
    if ($todayBookingCount > 0) {
        $venue['availability_status'] = 'booked_today';
        $venue['today_bookings'] = $todayBookingCount;
    } else {
        $venue['availability_status'] = 'available';
        $venue['today_bookings'] = 0;
    }
}
unset($venue);

echo "🎯 VENUE STATUS (Same logic as venue.php):\n";
echo str_repeat("=", 80) . "\n";

foreach ($allVenues as $venue) {
    $status = $venue['availability_status'];
    $bookings = $venue['today_bookings'];
    
    if ($status === 'booked_today') {
        $icon = '🔴';
        $badge = 'Booked Today';
        $button = 'Disabled';
    } else {
        $icon = '🟢';
        $badge = 'Available';
        $button = 'Active';
    }
    
    echo sprintf("%s %s - %s (Bookings today: %d)\n", 
        $icon, 
        $venue['name'], 
        $badge, 
        $bookings
    );
}

echo "\n🔍 DETAILED ANALYSIS:\n";
echo str_repeat("=", 80) . "\n";

// Show specific booking counts
echo "Today's booking counts by venue:\n";
foreach ($todayBookingsMap as $venueId => $count) {
    $venueName = '';
    foreach ($allVenues as $venue) {
        if ($venue['id'] === $venueId) {
            $venueName = $venue['name'];
            break;
        }
    }
    echo "  📌 $venueName (ID: $venueId): $count booking(s)\n";
}

echo "\n🎉 EXPECTED VENUE PAGE DISPLAY:\n";
echo str_repeat("=", 80) . "\n";

foreach ($allVenues as $venue) {
    $name = $venue['name'];
    $status = $venue['availability_status'];
    $bookings = $venue['today_bookings'];
    
    if ($status === 'booked_today') {
        echo "✅ $name should show:\n";
        echo "   🔴 Red 'Booked Today' badge\n";
        echo "   🟫 Gray 'Booked Today' button (disabled)\n";
        echo "   📍 Status: Not available today\n\n";
    } else {
        echo "🟢 $name should show:\n";
        echo "   🟢 Green 'Available' badge\n";
        echo "   🔴 Red 'Book This Venue' button (active)\n";
        echo "   📍 Status: Fully available\n\n";
    }
}

echo "🔗 TEST INSTRUCTIONS:\n";
echo str_repeat("-", 50) . "\n";
echo "1. Visit: index.php?page=venue\n";
echo "2. Check that venues match the expected display above\n";
echo "3. Oceanview Terrace should show as 'Booked Today' (red badge)\n";
echo "4. Other venues should show as 'Available' (green badges)\n\n";

if (count(array_filter($allVenues, function($v) { return $v['availability_status'] === 'booked_today'; })) > 0) {
    echo "✅ SUCCESS: At least one venue shows as booked today!\n";
    echo "✅ The venue availability system is working!\n";
} else {
    echo "❌ ISSUE: No venues show as booked today\n";
    echo "❌ Check if the booking was created for the correct date\n";
}
?>