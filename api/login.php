<?php
session_start();
header("Content-Type: application/json");

// Correct path
require_once __DIR__ . "/../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Email and password required"]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Remove sensitive info
        unset($user['password_hash']);

        // Set session for server-side auth
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];

        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => $user
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Query failed: " . $e->getMessage()
    ]);
}
