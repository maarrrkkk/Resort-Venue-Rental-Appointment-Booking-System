<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../lib/phpmailer/src/PHPMailer.php";
require_once __DIR__ . "/../lib/phpmailer/src/SMTP.php";
require_once __DIR__ . "/../lib/phpmailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$password = trim($data['password'] ?? '');

// Basic validation
if (!$name || !$email || !$phone || !$password) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "This email is already registered."]);
        exit;
    }

    // Check if phone already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = :phone");
    $stmt->execute(['phone' => $phone]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "This phone number is already registered."]);
        exit;
    }

    // Generate verification code
    $verificationCode = sprintf("%06d", mt_rand(0, 999999));

    // Store registration data in session
    $_SESSION['pending_registration'] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'password' => $password,
        'verification_code' => $verificationCode
    ];

    // Send verification email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'johnmarkaguilar405@gmail.com';
        $mail->Password = 'zogj dumt hkci uahm';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('johnmarkaguilar405@gmail.com', 'Paradise Resort');
        $mail->addReplyTo('johnmarkaguilar405@gmail.com', 'Paradise Resort');
        $mail->addAddress($email, $name);

        $mail->isHTML(false);
        $mail->Subject = 'Email Verification Code - Paradise Resort';
        $mail->Body = "Hello $name,\n\nYour verification code is: $verificationCode\n\nPlease enter this code to complete your registration.\n\nBest regards,\nParadise Resort Team";

        $mail->send();

        echo json_encode([
            "success" => true,
            "message" => "Verification code sent to your email. Please check your inbox and enter the code below.",
            "step" => "verify"
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to send verification email. Please try again."
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Something went wrong. Please try again."
    ]);
}
