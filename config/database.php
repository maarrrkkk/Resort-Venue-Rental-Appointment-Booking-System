<?php
/**
 * Resort Venue Rental Appointment Booking System - Database Connection
 * Auto-run setup if DB/tables are missing
 */

require_once __DIR__ . '/../includes/config.php';

$host   = env('DB_HOST', 'localhost');
$user   = env('DB_USER', 'root');
$pass   = env('DB_PASS', '');
$dbname = env('DB_NAME', 'resort_booking');
$port   = env('DB_PORT', '3306');

try {
    // Try to connect directly to the DB
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if a critical table exists (users)
    $check = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($check->rowCount() === 0) {
        // Run setup if schema missing
        require_once __DIR__ . "/../setup.php";
    }

} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        // Run setup if DB missing
        require_once __DIR__ . "/../setup.php";
    } else {
        die(json_encode([
            "success" => false,
            "message" => "Database connection failed: " . $e->getMessage()
        ]));
    }
}
