<?php
/**
 * Detailed User Investigation Script
 * Check exact status of all users and fix verification issues
 */

require_once __DIR__ . '/config/database.php';

echo "=== DETAILED USER INVESTIGATION ===\n\n";

try {
    // Get all users with detailed information
    $stmt = $pdo->query("SELECT id, name, email, phone, role, email_verified, created_at FROM users ORDER BY created_at");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "CURRENT USERS IN DATABASE:\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($users as $user) {
        $verified = $user['email_verified'] ? '✅ VERIFIED' : '❌ NOT VERIFIED';
        echo sprintf("%s %-20s %-30s %-10s %s\n", 
            $verified,
            $user['name'], 
            $user['email'], 
            $user['role'],
            $user['created_at']
        );
    }
    
    echo "\n";
    
    // Test the specific john@example.com user
    $testEmail = 'john@example.com';
    echo "TESTING LOGIN FOR: $testEmail\n";
    echo str_repeat("-", 50) . "\n";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User found in database\n";
        echo "   ID: " . $user['id'] . "\n";
        echo "   Name: " . $user['name'] . "\n";
        echo "   Email: " . $user['email'] . "\n";
        echo "   Role: " . $user['role'] . "\n";
        echo "   Phone: " . $user['phone'] . "\n";
        echo "   Email Verified: " . ($user['email_verified'] ? 'YES' : 'NO') . "\n";
        echo "   Password Hash exists: " . (!empty($user['password_hash']) ? 'YES' : 'NO') . "\n";
        
        // Test password verification
        $testPassword = 'admin123';
        if (!empty($user['password_hash']) && password_verify($testPassword, $user['password_hash'])) {
            echo "   Password Test: ✅ CORRECT\n";
        } else {
            echo "   Password Test: ❌ INCORRECT\n";
        }
        
        // Show what login API would return
        echo "\nLOGIN API SIMULATION:\n";
        if (!$user['email_verified']) {
            echo "❌ Result: 'Please verify your email before logging in.'\n";
        } elseif (password_verify($testPassword, $user['password_hash'])) {
            echo "✅ Result: Login successful\n";
        } else {
            echo "❌ Result: 'Invalid email or password'\n";
        }
        
    } else {
        echo "❌ User NOT found in database\n";
    }
    
    echo "\n";
    
    // Check if there are multiple john@example.com entries
    echo "CHECKING FOR DUPLICATE EMAILS:\n";
    $stmt = $pdo->prepare("SELECT email, COUNT(*) as count FROM users GROUP BY email HAVING COUNT(*) > 1");
    $stmt->execute();
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($duplicates) {
        echo "❌ DUPLICATE EMAILS FOUND:\n";
        foreach ($duplicates as $dup) {
            echo "   " . $dup['email'] . " - " . $dup['count'] . " entries\n";
        }
    } else {
        echo "✅ No duplicate emails found\n";
    }
    
    echo "\n";
    
    // Fix ALL users email verification
    echo "FIXING ALL USER EMAIL VERIFICATION:\n";
    $stmt = $pdo->query("UPDATE users SET email_verified = TRUE");
    $updated = $stmt->rowCount();
    echo "✅ Updated $updated users to email_verified = TRUE\n";
    
    echo "\n";
    
    // Verify the fix
    echo "VERIFICATION AFTER FIX:\n";
    $stmt = $pdo->query("SELECT name, email, email_verified FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $verified = $user['email_verified'] ? '✅' : '❌';
        echo "$verified " . $user['name'] . " (" . $user['email'] . ")\n";
    }
    
    echo "\n=== FINAL LOGIN TEST ===\n";
    
    // Test john@example.com login again
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => 'john@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['email_verified'] && password_verify('admin123', $user['password_hash'])) {
        echo "✅ JOHN@EXAMPLE.COM LOGIN SHOULD NOW WORK!\n";
        echo "   Email: john@example.com\n";
        echo "   Password: admin123\n";
    } else {
        echo "❌ JOHN@EXAMPLE.COM LOGIN STILL NOT WORKING\n";
        if (!$user['email_verified']) {
            echo "   Reason: Email not verified\n";
        }
        if (!password_verify('admin123', $user['password_hash'])) {
            echo "   Reason: Password incorrect\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== INVESTIGATION COMPLETE ===\n";
?>