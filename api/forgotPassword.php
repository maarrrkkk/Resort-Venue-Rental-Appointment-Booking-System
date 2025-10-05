<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../lib/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit;
}

try {

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Don't reveal if email exists or not for security
        echo json_encode(['success' => true, 'message' => 'If the email exists, a reset link has been sent.']);
        exit;
    }

    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires = gmdate('Y-m-d H:i:s', strtotime('+1 hour'));

    // Update user with reset token
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
    $stmt->execute([$token, $expires, $user['id']]);

    // Send email
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = env('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = env('SMTP_USERNAME', 'johnmarkaguilar405@gmail.com');
        $mail->Password = env('SMTP_PASSWORD', 'zogj dumt hkci uahm');
        $mail->SMTPSecure = env('SMTP_ENCRYPTION', 'tls') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = env('SMTP_PORT', 587);

        // Recipients
        $mail->setFrom(env('SMTP_USERNAME', 'johnmarkaguilar405@gmail.com'), env('SITE_NAME', 'Rental Resort'));
        $mail->addAddress($email, $user['name']);

        // Content
        $baseUrl = env('BASE_URL', 'http://localhost');
        $resetLink = $baseUrl . '/index.php?page=resetPassword&token=' . $token;
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - Rental Resort';
        $mail->Body = "
            <h2>Password Reset Request</h2>
            <p>Hello {$user['name']},</p>
            <p>You have requested to reset your password. Click the link below to reset your password:</p>
            <p><a href='{$resetLink}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you didn't request this, please ignore this email.</p>
            <p>Best regards,<br>Rental Resort Team</p>
        ";
        $mail->AltBody = "Hello {$user['name']},\n\nYou have requested to reset your password. Click the link below to reset your password:\n\n{$resetLink}\n\nThis link will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\Rental Resort Team";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Password reset link sent to your email!']);
    } catch (Exception $e) {
        error_log("Email send failed: " . $mail->ErrorInfo);
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>