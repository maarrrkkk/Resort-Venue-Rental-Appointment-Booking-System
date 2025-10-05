<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$token = trim($data['token'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($token) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Token and password are required']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

try {
    // Find user by token and check if not expired
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > UTC_TIMESTAMP() LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
        exit;
    }

    // Hash new password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Update password and clear reset token
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt->execute([$hashedPassword, $user['id']]);

    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>