<?php

// ✅ CORS
header("Access-Control-Allow-Origin: https://plantfront.onrender.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: false");

// ✅ Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// -------------------------
// PARSE JSON
// -------------------------
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Invalid JSON"
    ]);
    exit;
}

// -------------------------
// VALIDATE
// -------------------------
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$subject = trim($data['subject'] ?? 'No subject');
$message = trim($data['message'] ?? '');

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Missing fields"
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Invalid email"
    ]);
    exit;
}

// -------------------------
// RESEND
// -------------------------
$apiKey = getenv('RESEND_API_KEY');

if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Missing API key"
    ]);
    exit;
}

$payload = [
    "from" => "onboarding@resend.dev", // works immediately
    "to" => ["illiashapshalov38@gmail.com"],
    "subject" => "[Contact] " . $subject,
    "html" => "
        <h3>New message</h3>
        <p><b>Name:</b> $name</p>
        <p><b>Email:</b> $email</p>
        <p><b>Message:</b><br>$message</p>
    "
];

// -------------------------
// CURL
// -------------------------
$ch = curl_init("https://api.resend.com/emails");

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// -------------------------
// RESPONSE
// -------------------------
if ($error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $error
    ]);
    exit;
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode([
        "success" => true
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Resend failed",
        "status" => $httpCode,
        "response" => $response
    ]);
}