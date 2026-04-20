<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    exit("Forbidden");
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$message) {
    exit("All fields are required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Invalid email.");
}

$mail = new PHPMailer(true);

try {
    // -------------------------
    // SMTP CONFIG (SendGrid)
    // -------------------------
    $mail->isSMTP();
    $mail->Host = 'smtp.sendgrid.net';
    $mail->SMTPAuth = true;

    // IMPORTANT: SendGrid fixed username is always "apikey"
    $mail->Username = 'apikey';

    // Your SendGrid API key
    $mail->Password = getenv('SENDGRID_API_KEY');

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // -------------------------
    // EMAIL SETTINGS
    // -------------------------

    // MUST be a verified sender in SendGrid
    $mail->setFrom('illiashapshalov38@gmail.com', 'Plant Shop');

    $mail->addAddress('illiashapshalov38@gmail.com');

    // user reply goes to sender email
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

    $mail->send();

    echo "Message sent successfully";

} catch (Exception $e) {
    http_response_code(500);
    echo "Mailer Error: " . $mail->ErrorInfo;
}