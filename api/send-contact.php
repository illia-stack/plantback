<?php

// 🔥 Confirm request hits THIS file
error_log("CONTACT ENDPOINT HIT");

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
// DEBUG: RAW INPUT
// -------------------------
$raw = file_get_contents("php://input");
error_log("RAW INPUT: " . $raw);

// -------------------------
// JSON PARSE
// -------------------------
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Invalid JSON",
        "json_error" => json_last_error_msg()
    ]);
    exit;
}

// -------------------------
// SANITIZE
// -------------------------
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$subject = trim($data['subject'] ?? 'No subject');
$message = trim($data['message'] ?? '');

// -------------------------
// VALIDATE
// -------------------------
if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Missing fields",
        "received" => $data // DEBUG ONLY
    ]);
    exit;
}

// -------------------------
// SENDGRID
// -------------------------
$apiKey = getenv('SENDGRID_API_KEY');

if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Missing API key"
    ]);
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
// DEBUG RESPONSE
// -------------------------
echo json_encode([
    "success" => $httpCode >= 200 && $httpCode < 300,
    "sendgrid_status" => $httpCode,
    "sendgrid_response" => $response,
    "curl_error" => $error ?: null
]);