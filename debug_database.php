<?php
/**
 * Database Diagnostic Script
 * Tests database connection, table creation, and data access
 */

echo "=== DATABASE DIAGNOSTIC ===\n\n";

// Test database connection
try {
    require_once __DIR__ . '/includes/config.php';
    
    $host = env('DB_HOST', 'localhost');
    $user = env('DB_USER', 'root');
    $pass = env('DB_PASS', '');
    $dbname = env('DB_NAME', 'resort_booking');
    $port = env('DB_PORT', '3306');
    
    echo "1. Testing Database Connection...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✅ Database connection successful\n\n";
    
    // Test table existence
    echo "2. Checking Table Existence...\n";
    $tables = ['users', 'venues', 'bookings', 'payments', 'settings'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "   " . ($exists ? "✅" : "❌") . " Table '$table' " . ($exists ? "exists" : "MISSING") . "\n";
    }
    echo "\n";
    
    // Test users table access
    echo "3. Testing Users Table Access...\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✅ Users table accessible - " . $result['count'] . " records\n";
        
        // Show sample users
        $stmt = $pdo->query("SELECT id, name, email, role FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($users) {
            echo "   Sample users:\n";
            foreach ($users as $user) {
                echo "   - " . $user['name'] . " (" . $user['email'] . ") [" . $user['role'] . "]\n";
            }
        } else {
            echo "   ⚠️  No users found\n";
        }
    } catch (PDOException $e) {
        echo "   ❌ Users table error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test venues table access
    echo "4. Testing Venues Table Access...\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM venues");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✅ Venues table accessible - " . $result['count'] . " records\n";
        
        // Show sample venues
        $stmt = $pdo->query("SELECT id, name, category, price FROM venues LIMIT 5");
        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($venues) {
            echo "   Sample venues:\n";
            foreach ($venues as $venue) {
                echo "   - " . $venue['name'] . " (" . $venue['category'] . ") ₱" . $venue['price'] . "\n";
            }
        } else {
            echo "   ⚠️  No venues found\n";
        }
    } catch (PDOException $e) {
        echo "   ❌ Venues table error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test login query (simulate login.php)
    echo "5. Testing Login Query (simulating login.php)...\n";
    try {
        $email = 'admin@resort.com';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "   ✅ Login query successful for: $email\n";
            echo "   - User ID: " . $user['id'] . "\n";
            echo "   - Name: " . $user['name'] . "\n";
            echo "   - Role: " . $user['role'] . "\n";
            echo "   - Email verified: " . ($user['email_verified'] ? 'Yes' : 'No') . "\n";
        } else {
            echo "   ⚠️  No user found with email: $email\n";
        }
    } catch (PDOException $e) {
        echo "   ❌ Login query error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test registration query (simulate register.php)
    echo "6. Testing Registration Query (simulating register.php)...\n";
    try {
        $email = 'john@example.com';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $count = $stmt->fetchColumn();
        echo "   ✅ Registration query successful\n";
        echo "   - Users with email '$email': $count\n";
        
        $phone = '+1-234-567-8900';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = :phone");
        $stmt->execute(['phone' => $phone]);
        $count = $stmt->fetchColumn();
        echo "   - Users with phone '$phone': $count\n";
    } catch (PDOException $e) {
        echo "   ❌ Registration query error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test venues API query (simulate venues.php)
    echo "7. Testing Venues API Query (simulating venues.php)...\n";
    try {
        $stmt = $pdo->query("SELECT * FROM venues ORDER BY name");
        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($venues) {
            echo "   ✅ Venues query successful - " . count($venues) . " venues found\n";
            foreach ($venues as $venue) {
                $images = json_decode($venue['images'], true);
                $imageCount = is_array($images) ? count($images) : 0;
                echo "   - " . $venue['name'] . " (" . $imageCount . " images)\n";
            }
        } else {
            echo "   ⚠️  No venues found\n";
        }
    } catch (PDOException $e) {
        echo "   ❌ Venues query error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    echo "=== DIAGNOSTIC COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "\nTrying to run setup.php...\n";
    
    try {
        require_once __DIR__ . '/setup.php';
        echo "✅ Setup.php executed successfully\n";
    } catch (Exception $setupError) {
        echo "❌ Setup.php failed: " . $setupError->getMessage() . "\n";
    }
}
?>