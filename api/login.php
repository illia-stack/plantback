<?php
require_once __DIR__ . "/../includes/bootstrap.php";
require_once __DIR__ . "/../includes/db.php";

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if(!$data || json_last_error() !== JSON_ERROR_NONE)  {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit();
}

$email = strtolower(trim($data->email ?? ''));
$password = $data->password ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit();
}

try {
    validate_csrf();
    rate_limit('login', 10, 60);
    
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $valid = $user && password_verify($password, $user['password']);

    if (!$valid) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid credentials"]);
        exit();
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        "id" => $user["id"],
        "name" => $user["name"],
        "email" => $user["email"],
        "role" => $user["role"]
    ];  

   

    echo json_encode([  
        "success" => true,
        "user" => $_SESSION['user']
    ]);
    

} catch (PDOException $e) {
    error_log($e->getMessage()); 

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
}