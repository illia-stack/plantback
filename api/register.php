<?php

require_once "../includes/db.php";

header("Access-Control-Allow-Origin: https://plantfront.onrender.com");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Preflight (wichtig für React + Fetch)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// JSON lesen
$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit();
}

// Input validieren
$name = trim($data->name ?? '');
$email = trim($data->email ?? '');
$password = $data->password ?? '';

if ($name === '' || $email === '' || $password === '') {
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit();
}

// Email validieren (kleiner, aber wichtig)
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email"]);
    exit();
}

// Passwort hash
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    // optional: check if email already exists (robuster als DB error codes)
    $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);

    if ($check->fetch()) {
        echo json_encode(["success" => false, "message" => "Email already exists"]);
        exit();
    }

    // insert user
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

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
}