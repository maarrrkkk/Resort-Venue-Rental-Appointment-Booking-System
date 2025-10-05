<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../lib/phpmailer/src/PHPMailer.php';
require_once '../lib/phpmailer/src/SMTP.php';
require_once '../lib/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

$mail = new PHPMailer(true);

try {
    // Server settings for SMTP
    $mail->isSMTP();
    $mail->Host = env('SMTP_HOST', 'smtp.gmail.com');
    $mail->SMTPAuth = true;
    $mail->Username = env('SMTP_USERNAME', 'your-gmail@gmail.com');
    $mail->Password = env('SMTP_PASSWORD', 'your-16-character-app-password');
    $mail->SMTPSecure = env('SMTP_ENCRYPTION', 'tls') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = env('SMTP_PORT', 587);

    // Recipients
    $mail->setFrom(env('SMTP_USERNAME', 'your-gmail@gmail.com'), env('SITE_NAME', 'Paradise Resort'));
    $mail->addReplyTo($email, $name);
    $mail->addAddress(env('CONTACT_EMAIL', 'events@paradiseresort.com'), env('SITE_NAME', 'Paradise Resort Events'));

    // Content
    $mail->isHTML(false);
    $mail->Subject = 'New Quick Inquiry from ' . $name;
    $mail->Body = "Name: $name\nEmail: $email\n\nMessage:\n$message";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Inquiry sent successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to send inquiry: ' . $mail->ErrorInfo]);
}
?>