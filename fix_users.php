<?php
/**
 * Fix Default Users Email Verification
 * Sets email_verified = TRUE for default users so they can login immediately
 */

require_once __DIR__ . '/config/database.php';

try {
    echo "=== FIXING DEFAULT USERS EMAIL VERIFICATION ===\n\n";
    
    // Fix admin user
    $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE WHERE email = ?");
    $result = $stmt->execute(['admin@resort.com']);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Fixed admin user: admin@resort.com (now email_verified = TRUE)\n";
    } else {
        echo "⚠️  Admin user not found or already verified\n";
    }
    
    // Fix test users
    $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE WHERE email IN (?, ?)");
    $result = $stmt->execute(['john@example.com', 'sarah@example.com']);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Fixed test users: john@example.com, sarah@example.com (now email_verified = TRUE)\n";
    } else {
        echo "⚠️  Test users not found or already verified\n";
    }
    
    echo "\n=== VERIFICATION STATUS ===\n";
    
    // Show all users with verification status
    $stmt = $pdo->query("SELECT id, name, email, role, email_verified FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $verified = $user['email_verified'] ? '✅ VERIFIED' : '❌ NOT VERIFIED';
        echo sprintf("%s %s (%s) - %s\n", 
            $verified, 
            $user['name'], 
            $user['email'], 
            $user['role']
        );
    }
    
    echo "\n=== READY FOR LOGIN ===\n";
    echo "You can now login with:\n";
    echo "- admin@resort.com / admin123 (admin)\n";
    echo "- john@example.com / admin123 (client)\n";
    echo "- sarah@example.com / admin123 (client)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>