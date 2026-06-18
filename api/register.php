<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';

header("Content-Type: application/json");


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

// Email validieren (kleiner, aber wichtig)
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email"]);
    exit();
}

// Passwort hash
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    validate_csrf();
    // optional: check if email already exists (robuster als DB error codes)
    $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);

    if($check->fetch()){
        http_response_code(409);
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