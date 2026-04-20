<?php
// -------------------------
// CORS SETTINGS
// -------------------------
header("Access-Control-Allow-Origin: https://plant-shop-frontend.onrender.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// -------------------------
// JSON Response
// -------------------------
header('Content-Type: application/json');

// -------------------------
// Autoload & PHPMailer
// -------------------------
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// -------------------------
// POST Only
// -------------------------
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Forbidden"]);
    exit;
}

// -------------------------
// Form Data
// -------------------------
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$message) {
    echo json_encode(["success" => false, "error" => "All fields are required"]);
    exit;
}

// -------------------------
// Send Email via PHPMailer + SendGrid
// -------------------------
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.sendgrid.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'apikey';
    $apiKey = getenv('SENDGRID_API_KEY');
    if (!$apiKey) die("Missing SENDGRID_API_KEY");
    $mail->Password = $apiKey;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('illiashapshalov38@gmail.com', 'Plant Shop');
    $mail->addAddress('illiashapshalov38@gmail.com');
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = '[Contact Form] ' . $subject;
    $mail->Body = "<h3>New Contact Form Message</h3>
                   <p><b>Name:</b> {$name}</p>
                   <p><b>Email:</b> {$email}</p>
                   <p><b>Message:</b><br>{$message}</p>";
    $mail->AltBody = "Name: $name\nEmail: $email\nMessage: $message";

    $mail->send();

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $mail->ErrorInfo]);
}