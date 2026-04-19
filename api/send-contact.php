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
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = getenv('SMTP_EMAIL');
    $mail->Password = getenv('SMTP_PASSWORD');
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('illiashapshalov38@gmail.com', 'Contact Form');
    $mail->addAddress('illiashapshalov38@gmail.com');
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = '[Contact Form] ' . $subject;
    $mail->Body = "Name: $name<br>Email: $email<br><br>Message:<br>$message";

    $mail->send();
    echo "Message sent successfully";

} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}