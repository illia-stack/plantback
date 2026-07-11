<?php

// ✅ CORS (public endpoint)
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

// ✅ Parse JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid JSON"]);
    exit;
}

// ✅ Sanitize
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$subject = trim($data['subject'] ?? 'No subject');
$message = trim($data['message'] ?? '');

// ✅ Validate
if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing fields"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid email"]);
    exit;
}

// -------------------------
// SENDGRID
// -------------------------
$apiKey = getenv('SENDGRID_API_KEY');

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Missing API key"]);
    exit;
}

// -------------------------
// PAYLOAD
// -------------------------
$payload = [
    "personalizations" => [[
        "to" => [[ "email" => "illiashapshalov38@gmail.com" ]]
    ]],
    "from" => [
        "email" => "illiashapshalov38@gmail.com"
    ],
    "subject" => "[Contact] " . $subject,
    "content" => [[
        "type" => "text/html",
        "value" => "
            <h3>New message</h3>
            <p><b>Name:</b> $name</p>
            <p><b>Email:</b> $email</p>
            <p><b>Message:</b><br>$message</p>
        "
    ]]
];

// -------------------------
// CURL
// -------------------------
$ch = curl_init("https://api.sendgrid.com/v3/mail/send");

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
    echo json_encode(["success" => false, "error" => $error]);
    exit;
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(["success" => true]);
} else {
    http_response_code($httpCode);
    echo json_encode([
        "success" => false,
        "error" => "SendGrid failed",
        "status" => $httpCode,
        "response" => $response
    ]);
}