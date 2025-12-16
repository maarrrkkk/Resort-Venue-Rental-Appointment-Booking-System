<?php
/**
 * Investigate the booking discrepancy
 */

require_once 'config/database.php';

echo "=== BOOKING DISCREPANCY INVESTIGATION ===\n\n";

// Check different date scenarios
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$tomorrow = date('Y-m-d', strtotime('+1 day'));

echo "📅 DATE CHECK:\n";
echo "Today: $today\n";
echo "Yesterday: $yesterday\n";
echo "Tomorrow: $tomorrow\n\n";

// Check all bookings regardless of date
echo "📋 ALL BOOKINGS IN DATABASE:\n";
echo str_repeat("=", 80) . "\n";

$allBookingsStmt = $pdo->query("
    SELECT b.*, v.name as venue_name 
    FROM bookings b 
    JOIN venues v ON b.venue_id = v.id 
    ORDER BY b.created_at DESC
");

$allBookings = $allBookingsStmt->fetchAll();

foreach ($allBookings as $booking) {
    echo sprintf("ID: %s | Venue: %s | Date: %s | Status: %s | Created: %s\n",
        $booking['id'],
        $booking['venue_name'],
        $booking['booking_date'],
        $booking['status'],
        $booking['created_at']
    );
}

echo "\n🔍 SPECIFIC DATE ANALYSIS:\n";
echo str_repeat("=", 80) . "\n";

// Check each date
$datesToCheck = [$today, $yesterday, $tomorrow];
foreach ($datesToCheck as $date) {
    echo "\n📅 Bookings for $date:\n";
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE booking_date = ? 
        AND status IN ('confirmed', 'pending')
    ");
    $stmt->execute([$date]);
    $count = $stmt->fetch()['count'];
    
    echo "   Count: $count\n";
    
    if ($count > 0) {
        $stmt = $pdo->prepare("
            SELECT v.name, b.id, b.status 
            FROM bookings b 
            JOIN venues v ON b.venue_id = v.id 
            WHERE b.booking_date = ? 
            AND b.status IN ('confirmed', 'pending')
        ");
        $stmt->execute([$date]);
        $bookings = $stmt->fetchAll();
        
        foreach ($bookings as $booking) {
            echo "   - {$booking['name']} (ID: {$booking['id']}, Status: {$booking['status']})\n";
        }
    }
}

// Check for any bookings that might be causing confusion
echo "\n🔬 VENUE-SPECIFIC ANALYSIS:\n";
echo str_repeat("=", 80) . "\n";

$venues = ['venue1', 'venue3']; // Grand Ballroom and Executive Conference Center
foreach ($venues as $venueId) {
    echo "\n📍 Venue ID: $venueId\n";
    
    // Get venue name
    $venueStmt = $pdo->prepare("SELECT name FROM venues WHERE id = ?");
    $venueStmt->execute([$venueId]);
    $venue = $venueStmt->fetch();
    echo "   Name: {$venue['name']}\n";
    
    // Check bookings for each date
    foreach ($datesToCheck as $date) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE venue_id = ? 
            AND booking_date = ? 
            AND status IN ('confirmed', 'pending')
        ");
        $stmt->execute([$venueId, $date]);
        $count = $stmt->fetch()['count'];
        
        echo "   $date: $count booking(s)\n";
    }
}

echo "\n💡 CONCLUSION:\n";
echo str_repeat("=", 80) . "\n";

$todayBookings = 0;
foreach ($allBookings as $booking) {
    if ($booking['booking_date'] === $today && in_array($booking['status'], ['confirmed', 'pending'])) {
        $todayBookings++;
    }
}

echo "Bookings for today ($today): $todayBookings\n";

if ($todayBookings === 0) {
    echo "❌ No bookings for today - this explains why venues appear available\n";
    echo "💡 Your booking attempts may have failed or been created for different dates\n";
} else {
    echo "✅ Found bookings for today\n";
}
?>