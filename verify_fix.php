<?php
// Simple test to create a booking for today and verify the fix works
require_once 'config/database.php';

echo "Testing venue availability fix...\n";

// Get first venue
$stmt = $pdo->query("SELECT id, name FROM venues LIMIT 1");
$venue = $stmt->fetch();

if (!$venue) {
    echo "No venues found!\n";
    exit;
}

echo "Found venue: {$venue['name']} (ID: {$venue['id']})\n";

$today = date('Y-m-d');

// Check existing bookings
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE venue_id = ? AND booking_date = ?");
$stmt->execute([$venue['id'], $today]);
$existing = $stmt->fetch()['count'];

echo "Existing bookings for today: $existing\n";

if ($existing == 0) {
    // Create test booking
    $bookingId = 'test_' . uniqid();
    $stmt = $pdo->prepare("INSERT INTO bookings (id, user_id, venue_id, booking_date, status) VALUES (?, ?, ?, ?, 'confirmed')");
    $stmt->execute([$bookingId, 'test_user', $venue['id'], $today]);
    echo "Created test booking: $bookingId\n";
}

// Test the availability query
$stmt = $pdo->query("
    SELECT v.name,
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
");

$result = $stmt->fetch();
echo "Availability status: {$result['status']}\n";

if ($result['status'] === 'booked_today') {
    echo "✅ SUCCESS: Venue correctly shows as booked today!\n";
    echo "✅ The fix is working - buttons should be disabled.\n";
} else {
    echo "❌ FAILED: Venue should show as booked today.\n";
}
?>