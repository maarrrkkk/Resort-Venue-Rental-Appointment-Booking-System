<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$code = trim($data['code'] ?? '');

if (!$code) {
    echo json_encode(["success" => false, "message" => "Verification code is required"]);
    exit;
}

if (!isset($_SESSION['pending_registration'])) {
    echo json_encode(["success" => false, "message" => "No pending registration found. Please start registration again."]);
    exit;
}

$pending = $_SESSION['pending_registration'];

if ($code !== $pending['verification_code']) {
    echo json_encode(["success" => false, "message" => "Invalid verification code"]);
    exit;
}

try {
    // Generate user ID
    $userId = uniqid("user_");
    $passwordHash = password_hash($pending['password'], PASSWORD_BCRYPT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (id, name, email, phone, password_hash, role, email_verified)
                           VALUES (:id, :name, :email, :phone, :password_hash, 'client', TRUE)");
    $stmt->execute([
        'id' => $userId,
        'name' => $pending['name'],
        'email' => $pending['email'],
        'phone' => $pending['phone'],
        'password_hash' => $passwordHash
    ]);

    // Set session for server-side auth
    $_SESSION['user'] = [
        "id" => $userId,
        "name" => $pending['name'],
        "email" => $pending['email'],
        "phone" => $pending['phone'],
        "role" => "client"
    ];
    $_SESSION['user_id'] = $userId;

    // Clear pending registration
    unset($_SESSION['pending_registration']);

    echo json_encode([
        "success" => true,
        "message" => "Email verified and registration completed!",
        "user" => [
            "id" => $userId,
            "name" => $pending['name'],
            "email" => $pending['email'],
            "phone" => $pending['phone'],
            "role" => "client"
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Something went wrong while completing registration. Please try again."
    ]);
}
?>