<?php

    require_once "../../includes/db.php";

    header("Content-Type: application/json");

    $data = json_decode(file_get_contents("php://input"));

    $userId = $data->userId ?? null;

    if (!$userId) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Unauthorized"
        ]);

        exit();
    }

    $stmt = $conn->prepare("
        SELECT role
        FROM users
        WHERE id = :id
    ");

    $stmt->execute([
        ":id" => $userId
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user["role"] !== "admin") {

        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" => "Access denied"
        ]);

        exit();
    }

    /*
    ---------------------------------
    HIER ERST:
    Report erzeugen
    ---------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Admin access granted"
    ]);
?>