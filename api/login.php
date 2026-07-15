<?php
error_log("LOGIN STEP 1: start");
error_reporting(E_ALL);
ini_set('display_errors', 1);

    register_shutdown_function(function () {
    $error = error_get_last();

    if ($error !== NULL) {
        error_log("FATAL ERROR: " . print_r($error, true));

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "fatal" => $error['message']
        ]);
    }
});

    require_once __DIR__ . "/../includes/bootstrap.php";
    require_once __DIR__ . "/../includes/db.php";

    header("Content-Type: application/json");


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

        rate_limit('login', 10, 60);
        
        validate_csrf();
        error_log("LOGIN STEP 2: csrf passed");
            
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $valid = $user && password_verify($password, $user['password']);

        if (!$valid) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Invalid credentials"]);
            exit();
        }
error_log("LOGIN STEP 3: db query done");
error_log("LOGIN STEP 4: before session regenerate");
        session_regenerate_id(true);

        $_SESSION['user'] = [
            "id" => $user["id"],
            "name" => $user["name"],
            "email" => $user["email"],
            "role" => $user["role"]
        ];  

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    

        echo json_encode([  
            "success" => true,
            "user" => $_SESSION['user']
        ]);
        error_log("LOGIN STEP 5: session done");

    } catch (Throwable $e) {
        error_log("LOGIN ERROR: " . $e->getMessage());
error_log("FILE: " . $e->getFile() . " LINE: " . $e->getLine());
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "error" => $e->getMessage()
        ]);
    }

?>