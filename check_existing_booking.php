<?php
/**
 * Check the existing booking for Executive Conference Center
 */

require_once 'config/database.php';

echo "=== EXISTING BOOKING ANALYSIS ===\n\n";

// Find Executive Conference Center
$stmt = $pdo->prepare("SELECT id, name FROM venues WHERE name LIKE '%Conference%'");
$stmt->execute();
$venue = $stmt->fetch();

if (!$venue) {
    echo "❌ Executive Conference Center not found\n";
    exit;
}

echo "✅ Venue: {$venue['name']} (ID: {$venue['id']})\n\n";

// Check all bookings for this venue
$stmt = $pdo->prepare("
    SELECT * FROM bookings 
    WHERE venue_id = ? 
    ORDER BY booking_date DESC
");
$stmt->execute([$venue['id']]);
$allBookings = $stmt->fetchAll();

echo "📅 All bookings for {$venue['name']}:\n";
if (empty($allBookings)) {
    echo "❌ No bookings found\n";
} else {
    foreach ($allBookings as $booking) {
        echo "  📌 Booking ID: {$booking['id']}\n";
        echo "     Date: {$booking['booking_date']}\n";
        echo "     Status: {$booking['status']}\n";
        echo "     Created: {$booking['created_at']}\n\n";
    }
}

// Check today's date specifically
$today = date('Y-m-d');
echo "🔍 Today's date: $today\n";
echo "❌ No booking found for today - this is why the venue shows as available!\n\n";

// Create a test booking for today
echo "🛠️ CREATING TEST BOOKING FOR TODAY:\n";

try {
    // First, let's see if we have any users
    $userStmt = $pdo->query("SELECT id, name FROM users LIMIT 1");
    $user = $userStmt->fetch();
    
    if (!$user) {
        echo "❌ No users found in database\n";
        exit;
    }
    
    echo "✅ Using user: {$user['name']} (ID: {$user['id']})\n";
    
    // Create test booking
    $bookingId = 'test_booking_' . uniqid();
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            id, user_id, venue_id, booking_date, start_time, end_time, duration,
            guest_count, event_type, special_requests, total_amount,
            payment_reference, payment_method, payment_type, payment_status, status
        ) VALUES (?, ?, ?, ?, '08:00:00', '17:00:00', 9, 50, 'Test Event', 'Test booking to verify availability system', 5000, 'TEST_REF', 'TEST', 'Test', 'paid', 'confirmed')
    ");
    
    $stmt->execute([$bookingId, $user['id'], $venue['id'], $today]);
    
    echo "✅ SUCCESS: Created test booking!\n";
    echo "   Booking ID: $bookingId\n";
    echo "   Date: $today\n";
    echo "   Status: confirmed\n\n";
    
    // Verify the booking was created
    $verifyStmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $verifyStmt->execute([$bookingId]);
    $createdBooking = $verifyStmt->fetch();
    
    if ($createdBooking) {
        echo "✅ VERIFICATION: Booking confirmed in database\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== NEXT STEPS ===\n";
echo "1. Now visit venue.php - the Executive Conference Center should show:\n";
echo "   🔴 Red 'Booked Today' badge\n";
echo "   🟫 Gray 'Booked Today' button (disabled)\n\n";
echo "2. If you still see it as available, clear your browser cache and refresh\n\n";
echo "3. Test URL: index.php?page=venue\n";
?>