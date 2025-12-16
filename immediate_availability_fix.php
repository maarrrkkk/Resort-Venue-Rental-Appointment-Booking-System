<?php
/**
 * Immediate Venue Availability Fix
 * This creates a direct solution that bypasses all JavaScript complexity
 */

require_once 'config/database.php';

// Get all venues with their current availability status
$stmt = $pdo->query("
    SELECT v.*, 
           CASE 
               WHEN EXISTS (
                   SELECT 1 FROM bookings b 
                   WHERE b.venue_id = v.id 
                   AND b.booking_date = CURDATE() 
                   AND b.status IN ('confirmed', 'pending')
               ) THEN 'booked_today'
               WHEN EXISTS (
                   SELECT 1 FROM bookings b 
                   WHERE b.venue_id = v.id 
                   AND b.booking_date >= CURDATE() 
                   AND b.status IN ('confirmed', 'pending')
               ) THEN 'has_future_bookings'
               ELSE 'available'
           END as availability_status,
           (
               SELECT COUNT(DISTINCT b2.booking_date) 
               FROM bookings b2 
               WHERE b2.venue_id = v.id 
               AND b2.booking_date >= CURDATE() 
               AND b2.status IN ('confirmed', 'pending')
               AND b2.booking_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
           ) as booked_dates_count
    FROM venues v
    ORDER BY v.name
");

$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>Venue Availability - Fixed Version</title>\n";
echo "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>\n";
echo "    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>\n";
echo "    <style>\n";
echo "        .venue-card { transition: all 0.3s ease; }\n";
echo "        .venue-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }\n";
echo "        .availability-indicator { position: absolute; top: 10px; right: 10px; z-index: 10; }\n";
echo "        .debug-info { background: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container mt-4'>\n";
echo "    <h1 class='text-center mb-4'>\n";
echo "        <i class='fas fa-calendar-check text-primary'></i>\n";
echo "        Venue Availability - Fixed Version\n";
echo "    </h1>\n";

echo "    <div class='debug-info'>\n";
echo "        <h5><i class='fas fa-info-circle'></i> System Status</h5>\n";
echo "        <p><strong>Today's Date:</strong> " . date('Y-m-d') . "</p>\n";
echo "        <p><strong>Total Venues:</strong> " . count($venues) . "</p>\n";

// Check for today's bookings
$todayBookingsStmt = $pdo->query("
    SELECT v.name, b.booking_date, b.status 
    FROM bookings b 
    JOIN venues v ON b.venue_id = v.id 
    WHERE b.booking_date = CURDATE() 
    AND b.status IN ('confirmed', 'pending')
    ORDER BY b.created_at DESC
");
$todayBookings = $todayBookingsStmt->fetchAll(PDO::FETCH_ASSOC);

echo "        <p><strong>Today's Bookings:</strong> " . count($todayBookings) . "</p>\n";
if (count($todayBookings) > 0) {
    echo "        <ul>\n";
    foreach ($todayBookings as $booking) {
        echo "            <li><strong>{$booking['name']}</strong> - Status: {$booking['status']}</li>\n";
    }
    echo "        </ul>\n";
}

echo "    </div>\n";

echo "    <div class='row g-4'>\n";
foreach ($venues as $venue) {
    $amenities = json_decode($venue['amenities'], true) ?: [];
    $images = json_decode($venue['images'], true) ?: ['assets/images/default-venue-image.png'];
    $limitedAmenities = array_slice($amenities, 0, 3);
    $extraCount = count($amenities) - 3;
    
    // Determine availability status and styling
    switch ($venue['availability_status']) {
        case 'booked_today':
            $badgeClass = 'bg-danger';
            $badgeText = 'Booked Today';
            $buttonClass = 'btn-secondary';
            $buttonText = 'Booked Today';
            $buttonDisabled = 'disabled';
            $statusIcon = 'fas fa-times-circle';
            $statusColor = 'text-danger';
            $statusMessage = 'Not available today';
            break;
        case 'has_future_bookings':
            $badgeClass = 'bg-warning';
            $badgeText = 'Limited Availability';
            $buttonClass = 'btn-warning';
            $buttonText = 'Book (Some dates taken)';
            $buttonDisabled = '';
            $statusIcon = 'fas fa-exclamation-triangle';
            $statusColor = 'text-warning';
            $statusMessage = $venue['booked_dates_count'] . ' dates booked in next 30 days';
            break;
        default:
            $badgeClass = 'bg-success';
            $badgeText = 'Available';
            $buttonClass = 'btn-danger';
            $buttonText = 'Book This Venue';
            $buttonDisabled = '';
            $statusIcon = 'fas fa-check-circle';
            $statusColor = 'text-success';
            $statusMessage = 'Fully available';
    }
    
    echo "    <div class='col-lg-4 col-md-6'>\n";
    echo "        <div class='card venue-card h-100'>\n";
    echo "            <div class='position-relative'>\n";
    echo "                <img src='{$images[0]}' class='card-img-top' alt='{$venue['name']}' style='height: 200px; object-fit: cover;' onerror=\"this.src='assets/images/default-venue-image.png'\">\n";
    echo "                <span class='badge bg-secondary position-absolute top-0 start-0 m-2 text-capitalize'>{$venue['category']}</span>\n";
    echo "                <span class='badge {$badgeClass} availability-indicator'>{$badgeText}</span>\n";
    echo "            </div>\n";
    echo "            <div class='card-body d-flex flex-column'>\n";
    echo "                <h5 class='card-title'>{$venue['name']}</h5>\n";
    echo "                <p class='card-text text-muted'>{$venue['description']}</p>\n";
    echo "                \n";
    echo "                <div class='mb-3'>\n";
    echo "                    <small class='text-muted d-flex align-items-center mb-2'>\n";
    echo "                        <i class='fas fa-users me-2'></i>\n";
    echo "                        Capacity: {$venue['capacity']} guests\n";
    echo "                    </small>\n";
    echo "                    <small class='{$statusColor} d-flex align-items-center'>\n";
    echo "                        <i class='{$statusIcon} me-2'></i>\n";
    echo "                        {$statusMessage}\n";
    echo "                    </small>\n";
    echo "                </div>\n";
    echo "                \n";
    echo "                <div class='mb-3'>\n";
    echo "                    <h6 class='mb-2'>Amenities:</h6>\n";
    echo "                    <div class='amenity-list'>\n";
    foreach ($limitedAmenities as $amenity) {
        echo "                        <span class='badge bg-light text-dark amenity-badge'>{$amenity}</span>\n";
    }
    if ($extraCount > 0) {
        echo "                        <span class='badge bg-light text-dark amenity-badge'>+{$extraCount} more</span>\n";
    }
    echo "                    </div>\n";
    echo "                </div>\n";
    echo "                \n";
    echo "                <div class='mt-auto'>\n";
    echo "                    <div class='d-flex justify-content-between align-items-center border-top pt-3'>\n";
    echo "                        <div>\n";
    echo "                            <small class='text-muted'>Starting from</small>\n";
    echo "                            <div class='h5 mb-0'>₱" . number_format($venue['price']) . "</div>\n";
    echo "                        </div>\n";
    echo "                        <button class='btn {$buttonClass} venue-select-btn' \n";
    echo "                                data-venue-id='{$venue['id']}' \n";
    echo "                                {$buttonDisabled}\n";
    echo "                                title='{$statusMessage}'>\n";
    echo "                            {$buttonText}\n";
    echo "                        </button>\n";
    echo "                    </div>\n";
    echo "                </div>\n";
    echo "            </div>\n";
    echo "        </div>\n";
    echo "    </div>\n";
}

echo "    </div>\n";

echo "    <div class='mt-5 text-center'>\n";
echo "        <div class='alert alert-info'>\n";
echo "            <h5><i class='fas fa-check-circle'></i> This is the Fixed Version</h5>\n";
echo "            <p>This page shows venue availability directly from the database, bypassing any JavaScript issues.</p>\n";
echo "            <p><strong>If venues show correctly here but not on your main page, the issue is with your JavaScript.</strong></p>\n";
echo "        </div>\n";
echo "    </div>\n";

echo "    <div class='mt-4'>\n";
echo "        <h5>🔧 Quick Fix Instructions:</h5>\n";
echo "        <ol>\n";
echo "            <li>If venues show correctly on this page, copy the logic from this file to your main venue page</li>\n";
echo "            <li>The key is the SQL query that checks for bookings with today's date</li>\n";
echo "            <li>Replace your venue display code with the logic from this file</li>\n";
echo "            <li>Test by creating a booking for today and refreshing this page</li>\n";
echo "        </ol>\n";
echo "    </div>\n";

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
?>