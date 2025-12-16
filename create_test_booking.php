<?php
/**
 * Test Booking Creator - Creates a booking for today to test the venue availability fix
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>Create Test Booking</title>\n";
echo "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>\n";
echo "    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container mt-5'>\n";
echo "    <div class='row justify-content-center'>\n";
echo "        <div class='col-md-8'>\n";
echo "            <div class='card'>\n";
echo "                <div class='card-header'>\n";
echo "                    <h3><i class='fas fa-calendar-plus'></i> Create Test Booking for Today</h3>\n";
echo "                </div>\n";
echo "                <div class='card-body'>\n";

$today = date('Y-m-d');
echo "                    <p><strong>Today's Date:</strong> $today</p>\n";

// Get all venues
$stmt = $pdo->query("SELECT id, name FROM venues ORDER BY name");
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($venues)) {
    echo "                    <div class='alert alert-warning'>\n";
    echo "                        <i class='fas fa-exclamation-triangle'></i> No venues found in the database.\n";
    echo "                    </div>\n";
} else {
    echo "                    <p><strong>Available Venues:</strong></p>\n";
    echo "                    <ul>\n";
    foreach ($venues as $venue) {
        echo "                        <li>{$venue['name']} (ID: {$venue['id']})</li>\n";
    }
    echo "                    </ul>\n";
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['venue_id'])) {
        $venueId = $_POST['venue_id'];
        
        // Check if venue exists
        $stmt = $pdo->prepare("SELECT id, name FROM venues WHERE id = ?");
        $stmt->execute([$venueId]);
        $venue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$venue) {
            echo "                    <div class='alert alert-danger'>\n";
            echo "                        <i class='fas fa-times'></i> Venue not found!\n";
            echo "                    </div>\n";
        } else {
            // Check if there's already a booking for today
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM bookings 
                WHERE venue_id = ? 
                AND booking_date = ? 
                AND status IN ('confirmed', 'pending')
            ");
            $stmt->execute([$venueId, $today]);
            $existingBooking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingBooking['count'] > 0) {
                echo "                    <div class='alert alert-warning'>\n";
                echo "                        <i class='fas fa-exclamation-triangle'></i> This venue already has a booking for today!\n";
                echo "                    </div>\n";
            } else {
                // Create a test booking
                $bookingId = 'test_booking_' . uniqid();
                $stmt = $pdo->prepare("
                    INSERT INTO bookings (
                        id, user_id, venue_id, booking_date, start_time, end_time, duration,
                        guest_count, event_type, special_requests, total_amount,
                        payment_reference, payment_method, payment_type, payment_status, status
                    ) VALUES (?, ?, ?, ?, '08:00:00', '17:00:00', 9, 50, 'Test Event', 'Test booking for availability system', 5000, 'TEST_REF', 'TEST', 'Test', 'paid', 'confirmed')
                ");
                
                try {
                    $stmt->execute([$bookingId, 'test_user', $venueId, $today]);
                    echo "                    <div class='alert alert-success'>\n";
                    echo "                        <i class='fas fa-check'></i> <strong>Success!</strong> Created test booking for '{$venue['name']}' on $today.\n";
                    echo "                        <br><strong>Booking ID:</strong> $bookingId\n";
                    echo "                        <br><br><a href='pages/venue.php' class='btn btn-primary'>\n";
                    echo "                            <i class='fas fa-eye'></i> View Venue Page (Should show 'Booked Today')\n";
                    echo "                        </a>\n";
                    echo "                    </div>\n";
                } catch (PDOException $e) {
                    echo "                    <div class='alert alert-danger'>\n";
                    echo "                        <i class='fas fa-times'></i> Error creating booking: " . $e->getMessage() . "\n";
                    echo "                    </div>\n";
                }
            }
        }
    }
    
    // Show form
    echo "                    <hr>\n";
    echo "                    <h5><i class='fas fa-plus'></i> Create New Test Booking</h5>\n";
    echo "                    <form method='POST'>\n";
    echo "                        <div class='mb-3'>\n";
    echo "                            <label for='venue_id' class='form-label'>Select Venue:</label>\n";
    echo "                            <select class='form-select' id='venue_id' name='venue_id' required>\n";
    echo "                                <option value=''>-- Choose a venue --</option>\n";
    foreach ($venues as $venue) {
        echo "                                <option value='{$venue['id']}'>{$venue['name']}</option>\n";
    }
    echo "                            </select>\n";
    echo "                        </div>\n";
    echo "                        <button type='submit' class='btn btn-primary'>\n";
    echo "                            <i class='fas fa-calendar-plus'></i> Create Test Booking for Today\n";
    echo "                        </button>\n";
    echo "                    </form>\n";
    
    // Show existing bookings for today
    echo "                    <hr>\n";
    echo "                    <h5><i class='fas fa-list'></i> Today's Bookings</h5>\n";
    $stmt = $pdo->query("
        SELECT b.id, b.booking_date, b.status, v.name as venue_name
        FROM bookings b
        JOIN venues v ON b.venue_id = v.id
        WHERE b.booking_date = '$today'
        ORDER BY b.created_at DESC
    ");
    $todayBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($todayBookings)) {
        echo "                    <p class='text-muted'>No bookings for today.</p>\n";
    } else {
        echo "                    <div class='table-responsive'>\n";
        echo "                        <table class='table table-striped'>\n";
        echo "                            <thead>\n";
        echo "                                <tr>\n";
        echo "                                    <th>Booking ID</th>\n";
        echo "                                    <th>Venue</th>\n";
        echo "                                    <th>Status</th>\n";
        echo "                                    <th>Date</th>\n";
        echo "                                </tr>\n";
        echo "                            </thead>\n";
        echo "                            <tbody>\n";
        foreach ($todayBookings as $booking) {
            $statusClass = $booking['status'] === 'confirmed' ? 'success' : 'warning';
            echo "                                <tr>\n";
            echo "                                    <td>{$booking['id']}</td>\n";
            echo "                                    <td>{$booking['venue_name']}</td>\n";
            echo "                                    <td><span class='badge bg-{$statusClass}'>{$booking['status']}</span></td>\n";
            echo "                                    <td>{$booking['booking_date']}</td>\n";
            echo "                                </tr>\n";
        }
        echo "                            </tbody>\n";
        echo "                        </table>\n";
        echo "                    </div>\n";
    }
}

echo "                </div>\n";
echo "            </div>\n";
echo "        </div>\n";
echo "    </div>\n";
echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
?>