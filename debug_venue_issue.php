<?php
/**
 * Debug the venue availability issue
 */

require_once 'config/database.php';

echo "<h2>🔍 VENUE AVAILABILITY DEBUG</h2>\n";

try {
    // Check today's date
    $today = date('Y-m-d');
    echo "<p><strong>Today's Date:</strong> $today</p>\n";
    
    // Check all venues
    echo "<h3>📍 All Venues:</h3>\n";
    $stmt = $pdo->query("SELECT id, name FROM venues ORDER BY name");
    $venues = $stmt->fetchAll();
    
    foreach ($venues as $venue) {
        echo "<li><strong>{$venue['name']}</strong> (ID: {$venue['id']})</li>\n";
    }
    
    // Check bookings for today
    echo "<h3>📅 Today's Bookings ($today):</h3>\n";
    $stmt = $pdo->prepare("
        SELECT b.*, v.name as venue_name 
        FROM bookings b 
        JOIN venues v ON b.venue_id = v.id 
        WHERE b.booking_date = ? 
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$today]);
    $todayBookings = $stmt->fetchAll();
    
    if (empty($todayBookings)) {
        echo "<p>❌ No bookings found for today!</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Booking ID</th><th>Venue</th><th>Status</th><th>Created</th></tr>\n";
        foreach ($todayBookings as $booking) {
            echo "<tr>";
            echo "<td>{$booking['id']}</td>";
            echo "<td>{$booking['venue_name']}</td>";
            echo "<td>{$booking['status']}</td>";
            echo "<td>{$booking['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Check specific venue "Executive Conference Center"
    echo "<h3>🎯 Executive Conference Center Status:</h3>\n";
    $execVenueStmt = $pdo->prepare("SELECT id, name FROM venues WHERE name LIKE '%Executive Conference%' OR name LIKE '%Conference%'");
    $execVenueStmt->execute();
    $execVenue = $execVenueStmt->fetch();
    
    if (!$execVenue) {
        echo "<p>❌ Executive Conference Center not found!</p>\n";
    } else {
        echo "<p>✅ Found: {$execVenue['name']} (ID: {$execVenue['id']})</p>\n";
        
        // Check bookings for this specific venue today
        $venueBookingStmt = $pdo->prepare("
            SELECT * FROM bookings 
            WHERE venue_id = ? 
            AND booking_date = ? 
            AND status IN ('confirmed', 'pending')
        ");
        $venueBookingStmt->execute([$execVenue['id'], $today]);
        $venueBookings = $venueBookingStmt->fetchAll();
        
        if (empty($venueBookings)) {
            echo "<p>❌ No bookings found for Executive Conference Center today!</p>\n";
        } else {
            echo "<p>✅ Found " . count($venueBookings) . " booking(s) for Executive Conference Center today:</p>\n";
            foreach ($venueBookings as $booking) {
                echo "<li>Booking ID: {$booking['id']}, Status: {$booking['status']}</li>\n";
            }
        }
    }
    
    // Test the exact query used in venue.php
    echo "<h3>🧪 Testing venue.php availability query:</h3>\n";
    $availabilityStmt = $pdo->query("
        SELECT v.id, v.name,
               CASE 
                   WHEN EXISTS (
                       SELECT 1 FROM bookings b 
                       WHERE b.venue_id = v.id 
                       AND b.booking_date = CURDATE() 
                       AND b.status IN ('confirmed', 'pending')
                   ) THEN 'booked_today'
                   ELSE 'available'
               END as availability_status
        FROM venues v
        WHERE v.name LIKE '%Executive Conference%' OR v.name LIKE '%Conference%'
    ");
    $availabilityResult = $availabilityStmt->fetch();
    
    if ($availabilityResult) {
        echo "<p>Query Result: {$availabilityResult['name']} - Status: <strong>{$availabilityResult['availability_status']}</strong></p>\n";
    } else {
        echo "<p>❌ No result from availability query</p>\n";
    }
    
    // Check for any issues
    echo "<h3>🔍 Potential Issues:</h3>\n";
    echo "<ul>\n";
    
    // Check if booking was actually created
    if (empty($todayBookings)) {
        echo "<li>❌ No bookings exist for today - the booking may not have been created successfully</li>\n";
    }
    
    // Check for timezone issues
    echo "<li>📅 Current server time: " . date('Y-m-d H:i:s') . "</li>\n";
    echo "<li>🕐 MySQL CURDATE(): ";
    $mysqlDateStmt = $pdo->query("SELECT CURDATE() as today");
    $mysqlDate = $mysqlDateStmt->fetch()['today'];
    echo $mysqlDate . "</li>\n";
    
    if ($today !== $mysqlDate) {
        echo "<li>⚠️ Date mismatch detected! PHP date: $today, MySQL CURDATE(): $mysqlDate</li>\n";
    }
    
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>