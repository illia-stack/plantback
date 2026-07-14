<?php
    require_once __DIR__ . '/../includes/bootstrap.php';

    header("Content-Type: application/json");

    echo json_encode([
        "user" => $_SESSION['user'] ?? null
    ]);
?>