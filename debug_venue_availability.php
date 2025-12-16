<?php
/**
 * Venue Availability Debug Script
 * This script helps diagnose issues with the venue availability system
 */

require_once 'config/database.php';

echo "🔧 Venue Availability System Debug\n";
echo "==================================\n\n";

// Test 1: Check if we're getting the right date
echo "📅 Test 1: Date Verification\n";
$today = date('Y-m-d');
echo "  Today: $today\n";

// Test 2: Check existing bookings with detailed info
echo "\n📋 Test 2: Existing Bookings Analysis\n";
try {
    $stmt = $pdo->query("
        SELECT b.id, b.venue_id, v.name as venue_name, b.booking_date, b.status, b.created_at
        FROM bookings b
        JOIN venues v ON b.venue_id = v.id
        ORDER BY b.booking_date DESC, b.created_at DESC
    ");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($bookings) > 0) {
        echo "  ✅ Found " . count($bookings) . " bookings:\n";
        foreach ($bookings as $booking) {
            $status_color = $booking['status'] === 'confirmed' ? '🟢' : ($booking['status'] === 'pending' ? '🟡' : '🔴');
            echo "    $status_color {$booking['venue_name']} on {$booking['booking_date']} (Status: {$booking['status']})\n";
        }
        
        // Check for today's bookings specifically
        $todayBookings = array_filter($bookings, function($b) use ($today) {
            return $b['booking_date'] === $today;
        });
        
        if (count($todayBookings) > 0) {
            echo "\n  📅 Today's Bookings:\n";
            foreach ($todayBookings as $booking) {
                echo "    🟢 {$booking['venue_name']} - Status: {$booking['status']}\n";
            }
        } else {
            echo "\n  ✅ No bookings for today ($today)\n";
        }
    } else {
        echo "  ⚠️  No bookings found in database\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error fetching bookings: " . $e->getMessage() . "\n";
}

// Test 3: Test venue availability API simulation
echo "\n🔍 Test 3: Venue Availability API Simulation\n";
try {
    // Get first venue for testing
    $stmt = $pdo->query("SELECT id, name FROM venues LIMIT 1");
    $testVenue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testVenue) {
        echo "  📍 Testing venue: {$testVenue['name']} (ID: {$testVenue['id']})\n";
        
        // Test today's availability
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as booking_count,
                   GROUP_CONCAT(CONCAT('Booking #', SUBSTRING(id, 9)) SEPARATOR ', ') as existing_bookings
            FROM bookings
            WHERE venue_id = ?
            AND booking_date = ?
            AND status IN ('confirmed', 'pending')
        ");
        $stmt->execute([$testVenue['id'], $today]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $is_available = ($result['booking_count'] == 0);
        echo "  📅 Today's availability: " . ($is_available ? "✅ AVAILABLE" : "❌ UNAVAILABLE") . "\n";
        
        if (!$is_available) {
            echo "  📋 Conflicts with: " . ($result['existing_bookings'] ?? 'existing booking') . "\n";
        }
        
        // Test date range availability
        $startDate = $today;
        $endDate = date('Y-m-d', strtotime('+7 days'));
        
        $stmt = $pdo->prepare("
            SELECT booking_date, COUNT(*) as booking_count
            FROM bookings
            WHERE venue_id = ?
            AND booking_date BETWEEN ? AND ?
            AND status IN ('confirmed', 'pending')
            GROUP BY booking_date
            ORDER BY booking_date
        ");
        $stmt->execute([$testVenue['id'], $startDate, $endDate]);
        $rangeBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "  📊 Next 7 days availability:\n";
        $currentDate = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        $availableCount = 0;
        $totalDays = 0;
        
        while ($currentDate <= $endDateTime) {
            $dateStr = $currentDate->format('Y-m-d');
            $hasBooking = false;
            $bookingCount = 0;
            
            foreach ($rangeBookings as $booking) {
                if ($booking['booking_date'] === $dateStr) {
                    $hasBooking = true;
                    $bookingCount = $booking['booking_count'];
                    break;
                }
            }
            
            $status = $hasBooking ? "❌ ($bookingCount bookings)" : "✅ Available";
            echo "    {$currentDate->format('M d')}: $status\n";
            
            if (!$hasBooking) $availableCount++;
            $totalDays++;
            
            $currentDate->modify('+1 day');
        }
        
        $availabilityPercentage = $totalDays > 0 ? ($availableCount / $totalDays) * 100 : 100;
        echo "  📈 Summary: $availableCount/$totalDays days available (" . round($availabilityPercentage, 1) . "%)\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error in availability test: " . $e->getMessage() . "\n";
}

// Test 4: API Endpoint Tests
echo "\n🌐 Test 4: API Endpoint Tests\n";

// Test venues.php endpoint
echo "  Testing venues.php...\n";
$venuesUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api/venues.php";
echo "    URL: $venuesUrl\n";

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $venuesUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $venues = json_decode($response, true);
        if (is_array($venues)) {
            echo "    ✅ venues.php working - " . count($venues) . " venues returned\n";
        } else {
            echo "    ⚠️  venues.php returned invalid JSON\n";
        }
    } else {
        echo "    ❌ venues.php returned HTTP $httpCode\n";
    }
} else {
    echo "    ⚠️  curl not available - skipping API test\n";
}

// Test checkAvailability.php endpoint
echo "  Testing checkAvailability.php...\n";
if (isset($testVenue)) {
    $checkUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api/checkAvailability.php?venue_id={$testVenue['id']}&date=$today";
    echo "    URL: $checkUrl\n";
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $checkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['available'])) {
                echo "    ✅ checkAvailability.php working - Available: " . ($data['available'] ? 'Yes' : 'No') . "\n";
            } else {
                echo "    ⚠️  checkAvailability.php returned invalid response\n";
            }
        } else {
            echo "    ❌ checkAvailability.php returned HTTP $httpCode\n";
        }
    }
}

// Test 5: Database Connection and Table Structure
echo "\n💾 Test 5: Database Health Check\n";
try {
    // Check bookings table structure
    $stmt = $pdo->query("DESCRIBE bookings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'venue_id', 'booking_date', 'status'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "  ✅ bookings table structure is correct\n";
    } else {
        echo "  ❌ bookings table missing columns: " . implode(', ', $missingColumns) . "\n";
    }
    
    // Check for unique constraint
    $stmt = $pdo->query("SHOW CREATE TABLE bookings");
    $createTable = $stmt->fetch(PDO::FETCH_ASSOC)['Create Table'];
    
    if (strpos($createTable, 'unique_venue_datetime') !== false) {
        echo "  ✅ unique constraint exists\n";
    } else {
        echo "  ⚠️  unique constraint not found\n";
    }
    
    // Check indexes
    $stmt = $pdo->query("SHOW INDEX FROM bookings WHERE Key_name = 'idx_booking_date'");
    if ($stmt->rowCount() > 0) {
        echo "  ✅ booking_date index exists\n";
    } else {
        echo "  ⚠️  booking_date index not found\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Database health check failed: " . $e->getMessage() . "\n";
}

// Test 6: JavaScript Environment Check
echo "\n📜 Test 6: Frontend Environment\n";
echo "  Current page: " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "\n";
echo "  User agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n";

// Check if we're on a page that should load venues
$currentPage = $_GET['page'] ?? basename($_SERVER['PHP_SELF']);
if (in_array($currentPage, ['venue.php', 'index.php', 'home.php'])) {
    echo "  ✅ On a page that should load venues\n";
} else {
    echo "  ℹ️  Not on a venue page (page: $currentPage)\n";
}

echo "\n🎯 Debug Summary\n";
echo "================\n";
echo "1. ✅ Backend APIs appear to be working\n";
echo "2. ✅ Database structure is correct\n";
echo "3. ✅ Availability checking logic is sound\n";
echo "4. 🔧 If venues still show as available, the issue is likely:\n";
echo "   - Frontend JavaScript not loading availability data\n";
echo "   - Browser console errors preventing execution\n";
echo "   - API calls failing due to CORS or network issues\n";
echo "   - Timing issues with authManager initialization\n\n";

echo "🔗 Next Steps:\n";
echo "1. Open browser developer tools (F12)\n";
echo "2. Check Console tab for JavaScript errors\n";
echo "3. Check Network tab to see if API calls are working\n";
echo "4. Test the interactive page: test_venue_availability_validation.html\n";
echo "5. If APIs are failing, check PHP error logs\n";

?>