<?php
require_once __DIR__ '/..includes/bootstrap.php';
header("Access-Control-Allow-Origin: https://plantfront.onrender.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// -------------------------
// POST ONLY
// -------------------------
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Forbidden"]);
    exit;
}


// -------------------------
// DATA
// -------------------------

validate_csrf();

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$subject = trim($data['subject'] ?? '');
$message = trim($data['message'] ?? '');

if (!$name || !$email || !$message) {
    echo json_encode(["success" => false, "error" => "Missing fields"]);
    exit;
}

// -------------------------
// SENDGRID API KEY
// -------------------------
$apiKey = getenv('SENDGRID_API_KEY');

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Missing API key"]);
    exit;
}

// -------------------------
// EMAIL PAYLOAD
// -------------------------
$payload = [
    "personalizations" => [[
        "to" => [[
            "email" => "illiashapshalov38@gmail.com"
        ]]
    ]],
    "from" => [
        "email" => "illiashapshalov38@gmail.com"
    ],
    "subject" => "[Contact Form] " . $subject,
    "content" => [[
        "type" => "text/html",
        "value" => "
            <h3>New Contact Message</h3>
            <p><b>Name:</b> $name</p>
            <p><b>Email:</b> $email</p>
            <p><b>Message:</b><br>$message</p>
        "
    ]]
];

// -------------------------
// CURL REQUEST
// -------------------------
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api.sendgrid.com/v3/mail/send");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$curlError = curl_error($ch);
curl_close($ch);

// -------------------------
// RESPONSE HANDLING
// -------------------------
if ($curlError) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $curlError
    ]);
    exit;
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(["success" => true]);
} else {
    http_response_code($httpCode);
    echo json_encode([
        "success" => false,
        "error" => "SendGrid error",
        "status" => $httpCode,
        "response" => $response
    ]);
}