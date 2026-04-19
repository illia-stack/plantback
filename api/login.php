<?php
require_once "../includes/db.php";

header('Access-Control-Allow-Origin: https://plant-shop-frontend.onrender.com');
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

$email = $data->email;
$password = $data->password;

$stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user["password"])) {
    echo json_encode([
        "success" => true,
        "user" => [
            "id" => $user["id"],
            "name" => $user["name"]
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid credentials"
    ]);
}
?>