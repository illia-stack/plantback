<?php
require_once "../includes/db.php";

header('Access-Control-Allow-Origin: https://plant-shop-frontend.onrender.com');
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

$name = $data->name;
$email = $data->email;
$password = password_hash($data->password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $password);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
?>