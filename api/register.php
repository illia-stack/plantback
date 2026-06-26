<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';

header("Content-Type: application/json");

// DEBUG (später entfernen!)
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    rate_limit('register', 5, 60); // 5 requests per minute
    // JSON lesen
    $data = json_decode(file_get_contents("php://input"));

    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid JSON"]);
        exit();
    }

    // Input validieren
    $name = trim($data->name ?? '');
    $email = trim($data->email ?? '');
    $password = $data->password ?? '';

    if ($name === '' || $email === '' || $password === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "All fields required"]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid email"]);
        exit();
    }

    // 🔐 CSRF prüfen
    validate_csrf();

    // Passwort hash
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Check email
    $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);

    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Email already exists"]);
        exit();
    }

    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password)
        VALUES (:name, :email, :password)
    ");

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashedPassword
    ]);

    echo json_encode([
        "success" => true,
        "user" => [
            "name" => $name,
            "email" => $email
        ]
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Server error",
        "debug" => $e->getMessage() // 🔥 hilft dir jetzt
    ]);
}