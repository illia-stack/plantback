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
            echo json_encode([
                "success" => false,
                "errors" => ["general" => ["Invalid JSON input"]]
            ]);
            exit();
        }




        // Validate the input
        $name = trim($data->name ?? '');
        $email = strtolower(trim($data->email ?? ''));
        $password = $data->password ?? '';
        $errors = [];



        // Name validation
        if ($name === '') {
            $errors['name'][] = "Name is required";
        } elseif (strlen($name) < 2) {
            $errors['name'][] = "Name must be at least 2 characters";
        }

        // Email validation
        if ($email === '') {
            $errors['email'][] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = "Invalid email format";
        }

        // Password validation (granular)
        if ($password === '') {
            $errors['password'][] = "Password is required";
        } else {
            if (strlen($password) < 8) {
                $errors['password'][] = "Must be at least 8 characters";
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors['password'][] = "Must include at least one uppercase letter";
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors['password'][] = "Must include at least one lowercase letter";
            }
            if (!preg_match('/\d/', $password)) {
                $errors['password'][] = "Must include at least one number";
            }
            if (!preg_match('/[\W_]/', $password)) {
                $errors['password'][] = "Must include at least one special character";
            }
        }

        // If any errors → return all at once
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode([
                "success" => false,
                "errors" => $errors
            ]);
            exit();
        }




        //Avoid an email duplication
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $errors['email'][] = "Email is already registered";

            http_response_code(422);
            echo json_encode([
                "success" => false,
                "errors" => $errors
            ]);
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
            "errors" => [
                "general" => ["Server error"]
            ]
        ]);
    }

?>