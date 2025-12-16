<?php
/**
 * Check current venue status right now
 */

require_once 'config/database.php';

echo "=== CURRENT VENUE STATUS CHECK ===\n\n";

$today = date('Y-m-d');
$currentTime = date('Y-m-d H:i:s');
echo "Current time: $currentTime\n";
echo "Today's date: $today\n\n";

// Get all venues with their current booking status
echo "📍 CURRENT VENUE STATUS:\n";
echo str_repeat("=", 80) . "\n";

$venuesStmt = $pdo->query("SELECT * FROM venues ORDER BY name");
$allVenues = $venuesStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allVenues as $venue) {
    // Check today's bookings for this venue
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE venue_id = ? 
        AND booking_date = ? 
        AND status IN ('confirmed', 'pending')
    ");
    $stmt->execute([$venue['id'], $today]);
    $todayBookingCount = $stmt->fetch()['count'];
    
    if ($todayBookingCount > 0) {
        $status = "🔴 BOOKED TODAY";
        $button = "Disabled";
    } else {
        $status = "🟢 AVAILABLE";
        $button = "Active";
    }
    
    echo sprintf("%s %s (ID: %s) - %s bookings today\n", 
        $status, 
        $venue['name'], 
        $venue['id'], 
        $todayBookingCount
    );
}

// Check for any bookings created very recently
echo "\n📅 RECENT BOOKINGS (Last 30 minutes):\n";
echo str_repeat("=", 80) . "\n";

$thirtyMinutesAgo = date('Y-m-d H:i:s', strtotime('-30 minutes'));
$recentBookingsStmt = $pdo->prepare("
    SELECT b.*, v.name as venue_name 
    FROM bookings b 
    JOIN venues v ON b.venue_id = v.id 
    WHERE b.created_at >= ? 
    ORDER BY b.created_at DESC
");
$recentBookingsStmt->execute([$thirtyMinutesAgo]);
$recentBookings = $recentBookingsStmt->fetchAll();

if (empty($recentBookings)) {
    echo "❌ No bookings created in the last 30 minutes\n";
} else {
    echo "✅ Found " . count($recentBookings) . " recent booking(s):\n";
    foreach ($recentBookings as $booking) {
        echo "  📌 {$booking['venue_name']} - Date: {$booking['booking_date']} - Status: {$booking['status']}\n";
        echo "     Created: {$booking['created_at']}\n";
    }
}

// Test the exact logic used in venue.php
echo "\n🧪 TESTING VENUE.PHP LOGIC:\n";
echo str_repeat("=", 80) . "\n";

// Get today's bookings grouped by venue
$todayBookingsStmt = $pdo->query("
    SELECT venue_id, COUNT(*) as booking_count
    FROM bookings 
    WHERE booking_date = CURDATE() 
    AND status IN ('confirmed', 'pending')
    GROUP BY venue_id
");

$todayBookingsMap = [];
while ($booking = $todayBookingsStmt->fetch(PDO::FETCH_ASSOC)) {
    $todayBookingsMap[$booking['venue_id']] = $booking['booking_count'];
}

echo "Venue status based on today's bookings:\n";
foreach ($allVenues as $venue) {
    $venueId = $venue['id'];
    $todayCount = $todayBookingsMap[$venueId] ?? 0;
    
    if ($todayCount > 0) {
        $expectedStatus = "Should show: 🔴 BOOKED TODAY";
    } else {
        $expectedStatus = "Should show: 🟢 AVAILABLE";
    }
    
    echo sprintf("  %s - %s (Count: %d)\n", 
        $venue['name'], 
        $expectedStatus, 
        $todayCount
    );
}

// Check for any caching or display issues
echo "\n🔍 DIAGNOSTIC QUESTIONS:\n";
echo str_repeat("=", 80) . "\n";

echo "Which venue are you trying to book?\n";
echo "What do you see on the venue page right now?\n";
echo "Are you seeing the correct availability badges?\n\n";

echo "💡 IF A VENUE STILL SHOWS AS AVAILABLE:\n";
echo "1. ✅ This means there are NO bookings for that venue today\n";
echo "2. ✅ The availability system is working correctly\n";
echo "3. ✅ Your booking attempt may have failed or been for a different date\n\n";

echo "🎯 IMMEDIATE ACTION:\n";
echo "Tell me which specific venue is still showing as available,\n";
echo "and I'll create a test booking for that venue for today!\n";
?>