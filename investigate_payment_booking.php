<?php
/**
 * Investigate payment booking issues
 */

require_once 'config/database.php';

echo "=== INVESTIGATING PAYMENT BOOKING FAILURE ===\n\n";

$today = date('Y-m-d');
echo "Today's date: $today\n\n";

// Check all recent bookings (last 24 hours)
echo "📅 RECENT BOOKINGS (Last 24 hours):\n";
echo str_repeat("=", 80) . "\n";

$yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
$recentBookingsStmt = $pdo->prepare("
    SELECT b.*, v.name as venue_name 
    FROM bookings b 
    JOIN venues v ON b.venue_id = v.id 
    WHERE b.created_at >= ? 
    ORDER BY b.created_at DESC
");
$recentBookingsStmt->execute([$yesterday]);
$recentBookings = $recentBookingsStmt->fetchAll();

if (empty($recentBookings)) {
    echo "❌ No bookings created in the last 24 hours!\n";
} else {
    echo "✅ Found " . count($recentBookings) . " recent booking(s):\n\n";
    foreach ($recentBookings as $i => $booking) {
        echo "🏢 BOOKING #" . ($i + 1) . ":\n";
        echo "   Venue: {$booking['venue_name']} (ID: {$booking['venue_id']})\n";
        echo "   Booking Date: {$booking['booking_date']}\n";
        echo "   Status: {$booking['status']}\n";
        echo "   Payment: {$booking['payment_method']} - {$booking['payment_status']}\n";
        echo "   Created: {$booking['created_at']}\n";
        echo "   Booking ID: {$booking['id']}\n\n";
    }
}

// Check for any bookings for today
echo "📅 BOOKINGS FOR TODAY ($today):\n";
echo str_repeat("=", 80) . "\n";

$todayBookingsStmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM bookings 
    WHERE booking_date = ? 
    AND status IN ('confirmed', 'pending')
");
$todayBookingsStmt->execute([$today]);
$todayBookingCount = $todayBookingsStmt->fetch()['count'];

echo "Total bookings for today: $todayBookingCount\n";

if ($todayBookingCount > 0) {
    $todayBookingsDetailStmt = $pdo->prepare("
        SELECT v.name, b.id, b.status, b.payment_status, b.created_at
        FROM bookings b 
        JOIN venues v ON b.venue_id = v.id 
        WHERE b.booking_date = ? 
        AND b.status IN ('confirmed', 'pending')
        ORDER BY b.created_at DESC
    ");
    $todayBookingsDetailStmt->execute([$today]);
    $todayBookingsDetail = $todayBookingsDetailStmt->fetchAll();
    
    echo "Today's booking details:\n";
    foreach ($todayBookingsDetail as $booking) {
        echo "  📌 {$booking['name']} - {$booking['status']} - {$booking['payment_status']}\n";
        echo "     ID: {$booking['id']} | Created: {$booking['created_at']}\n";
    }
} else {
    echo "❌ No bookings found for today!\n";
}

// Check all venues status for today
echo "\n📍 VENUE STATUS FOR TODAY:\n";
echo str_repeat("=", 80) . "\n";

$venuesStmt = $pdo->query("SELECT * FROM venues ORDER BY name");
$venues = $venuesStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($venues as $venue) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE venue_id = ? 
        AND booking_date = ? 
        AND status IN ('confirmed', 'pending')
    ");
    $stmt->execute([$venue['id'], $today]);
    $bookingCount = $stmt->fetch()['count'];
    
    if ($bookingCount > 0) {
        $status = "🔴 BOOKED TODAY";
    } else {
        $status = "🟢 AVAILABLE";
    }
    
    echo sprintf("%s %s (Bookings today: %d)\n", $status, $venue['name'], $bookingCount);
}

// Analysis and recommendations
echo "\n🔍 ANALYSIS:\n";
echo str_repeat("=", 80) . "\n";

if (empty($recentBookings)) {
    echo "❌ CRITICAL ISSUE: No recent bookings found!\n";
    echo "💡 This means the payment process is not creating bookings at all.\n";
    echo "💡 Check the payment success page and booking creation logic.\n";
} elseif ($todayBookingCount === 0) {
    echo "❌ ISSUE: Bookings are being created but not for today.\n";
    echo "💡 Check if the booking date is being set correctly during payment.\n";
} else {
    echo "✅ Bookings are being created for today.\n";
    echo "✅ If venues still show as available, there may be a display issue.\n";
}

echo "\n🛠️ IMMEDIATE ACTIONS NEEDED:\n";
echo str_repeat("-", 50) . "\n";
echo "1. Check payment_success.php - ensure bookings are created after payment\n";
echo "2. Verify booking creation in api/createBooking.php\n";
echo "3. Check if the booking date matches today's date\n";
echo "4. Ensure payment status is 'paid' and booking status is 'confirmed'\n";

echo "\n🎯 TO REPRODUCE THE ISSUE:\n";
echo "1. Try to book a venue and complete payment\n";
echo "2. Check if a booking record is created in the database\n";
echo "3. Verify the venue page shows the correct status\n";
?>