<?php
require_once "../includes/db.php";

header('Access-Control-Allow-Origin: https://plant-shop-frontend.onrender.com');
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

$name = trim($data->name ?? '');
$email = trim($data->email ?? '');
$password = $data->password ?? '';

if (!$name || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit();
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashedPassword
    ]);

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    if ($e->getCode() == 23505) { // PostgreSQL unique violation
        echo json_encode(["success" => false, "message" => "Email already exists"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}