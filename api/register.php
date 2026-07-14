<?php
    require_once __DIR__ . '/../includes/bootstrap.php';
    require_once __DIR__ . '/../includes/db.php';

    header("Content-Type: application/json");

    
    try {
        rate_limit('register', 5, 60); // 5 requests per minute

        // 🔐 Check the CSRF 
        validate_csrf();

    
        // Parse the JSON 
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid JSON"]);
            exit();
        }

        // Validate the input
        $name = trim($data->name ?? '');
        $email = strtolower(trim($data->email ?? ''));
        $password = $data->password ?? '';

        if ($name === '' || $email === '' || $password === '') {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "All fields required"]);
            exit();
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Password must be at least 8 characters and include uppercase, lowercase, number and special character"
            ]);
            exit();
        }

        

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid email"]);
            exit();
        }

        
        // Passwort hash
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12
        ]);


        

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
        ]);
    }
?>