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
    // RATE LIMIT (simple file-based)
    // -------------------------
    $ip = $_SERVER['REMOTE_ADDR'];
    $limit = 5; // max requests
    $window = 60; // seconds

    $rateFile = sys_get_temp_dir() . "/rate_" . md5($ip);

    if (file_exists($rateFile)) {
        $rateData = json_decode(file_get_contents($rateFile), true);

        if (!is_array($rateData)) {
            $rateData = ["count" => 1, "time" => time()];
        }

        if ($rateData['time'] > time() - $window) {
            if ($rateData['count'] >= $limit) {
                http_response_code(429);
                echo json_encode([
                    "success" => false,
                    "error" => "Too many requests. Try again later."
                ]);
                exit;
            }
            $rateData['count']++;
        } else {
            $rateData = ["count" => 1, "time" => time()];
        }
    } else {
        $rateData = ["count" => 1, "time" => time()];
    }

    file_put_contents($rateFile, json_encode($rateData));



    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
        http_response_code(415);
        echo json_encode([
            "success" => false,
            "error" => "Invalid content type"
        ]);
        exit;
    }




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

    if (!empty($data['website'])) {
        http_response_code(400);
        exit;
    }




    // -------------------------
    // VALIDATE
    // -------------------------
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $subject = trim($data['subject'] ?? 'No subject');
    $message = trim($data['message'] ?? '');



    if (
        strlen($name) > 100 ||
        strlen($email) > 150 ||
        strlen($subject) > 150 ||
        strlen($message) > 5000
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Input too long"
        ]);
        exit;
    }



    $nameSafe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $emailSafe = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $subjectSafe = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $messageSafe = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

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
        "subject" => "[Contact] " . $subjectSafe,
        "html" => "
            <h3>New message</h3>
            <p><b>Name:</b> $nameSafe</p>
            <p><b>Email:</b> $emailSafe</p>
            <p><b>Message:</b><br>$messageSafe</p>
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
            "error" => "Email service error"
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
            "error" => "Email service error",
            "status" => $httpCode
        ]);
    }

?>