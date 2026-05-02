<?php
header("Access-Control-Allow-Origin: https://plantfront.onrender.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Forbidden"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$subject = trim($data['subject'] ?? '');
$message = trim($data['message'] ?? '');

if (!$name || !$email || !$message) {
    echo json_encode(["success" => false, "error" => "All fields are required"]);
    exit;
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.sendgrid.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'apikey';

    $apiKey = getenv('SENDGRID_API_KEY');
    error_log("SENDGRID KEY LENGTH: " . ($apiKey ? strlen($apiKey) : 0));

    if (!$apiKey) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Missing API key"]);
        exit;
    }

    $mail->Password = $apiKey;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // ✅ WICHTIG: VOR send()
    $mail->Timeout = 10;

    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {
        error_log("SMTP DEBUG: $str");
    };

    $mail->setFrom('illiashapshalov38@gmail.com', 'Plant Shop');
    $mail->addAddress('illiashapshalov38@gmail.com');
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = '[Contact Form] ' . $subject;
    $mail->Body = "
        <h3>New Contact Form Message</h3>
        <p><b>Name:</b> {$name}</p>
        <p><b>Email:</b> {$email}</p>
        <p><b>Message:</b><br>{$message}</p>
    ";

    $mail->AltBody = "Name: $name\nEmail: $email\nMessage: $message";

    // 🔥 TEST OPTION (optional aktivieren!)
    // echo json_encode(["success" => true, "debug" => "mail skipped"]);
    // exit;

    $mail->send();

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $mail->ErrorInfo
    ]);
}