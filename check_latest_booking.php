<?php
/**
 * Check the latest booking attempt and all venues status
 */

require_once 'config/database.php';

echo "=== LATEST BOOKING STATUS CHECK ===\n\n";

$today = date('Y-m-d');
echo "Today's date: $today\n\n";

// Check all venues and their current booking status
echo "📍 ALL VENUES STATUS:\n";
echo str_repeat("-", 60) . "\n";

$stmt = $pdo->query("
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
");

$venues = $stmt->fetchAll();

foreach ($venues as $venue) {
    $status_icon = $venue['availability_status'] === 'booked_today' ? '🔴' : '🟢';
    $status_text = $venue['availability_status'] === 'booked_today' ? 'BOOKED TODAY' : 'AVAILABLE';
    $booking_count = $venue['today_bookings'];
    
    echo sprintf("%s %s (ID: %s) - %s", 
        $status_icon, 
        $venue['name'], 
        $venue['id'], 
        $status_text
    );
    
    if ($booking_count > 0) {
        echo " ($booking_count booking" . ($booking_count > 1 ? "s" : "") . ")";
    }
    echo "\n";
}

// Check all bookings for today
echo "\n📅 TODAY'S BOOKINGS (" . count($venues) . " venues checked):\n";
echo str_repeat("-", 60) . "\n";

$todayBookingsStmt = $pdo->prepare("
    SELECT b.*, v.name as venue_name 
    FROM bookings b 
    JOIN venues v ON b.venue_id = v.id 
    WHERE b.booking_date = ? 
    ORDER BY b.created_at DESC
");
$todayBookingsStmt->execute([$today]);
$todayBookings = $todayBookingsStmt->fetchAll();

if (empty($todayBookings)) {
    echo "❌ NO BOOKINGS FOUND FOR TODAY ($today)\n";
    echo "\n💡 CONCLUSION: Your booking attempts are failing to create database records!\n";
    echo "💡 This is why venues still show as available.\n";
} else {
    echo "✅ Found " . count($todayBookings) . " booking(s) for today:\n";
    foreach ($todayBookings as $booking) {
        echo "  📌 {$booking['venue_name']} - Status: {$booking['status']} (ID: {$booking['id']})\n";
    }
}

// Show the problem
echo "\n🔍 ANALYSIS:\n";
echo str_repeat("-", 60) . "\n";

$availableCount = count(array_filter($venues, function($v) { return $v['availability_status'] === 'available'; }));
$bookedCount = count(array_filter($venues, function($v) { return $v['availability_status'] === 'booked_today'; }));

echo "✅ Available venues: $availableCount\n";
echo "🔴 Booked venues: $bookedCount\n";
echo "📊 Total venues: " . count($venues) . "\n";

if ($bookedCount == 0) {
    echo "\n❌ PROBLEM IDENTIFIED:\n";
    echo "- No venues are showing as booked for today\n";
    echo "- This means your booking attempts are not creating database records\n";
    echo "- The venue availability system is working correctly\n";
    echo "- The issue is in the booking/payment process\n";
    
    echo "\n🛠️ RECOMMENDATIONS:\n";
    echo "1. Check if you're completing the payment process\n";
    echo "2. Look for error messages during booking\n";
    echo "3. Try booking with a different payment method\n";
    echo "4. Check browser console for JavaScript errors\n";
}

echo "\n🎯 TO TEST THE FIX:\n";
echo "Visit: create_test_booking.php to create a proper booking\n";
?>