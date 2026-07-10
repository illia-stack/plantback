<?php

require_once __DIR__ . '/../includes/bootstrap.php';

header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    http_response_code(200);
    exit();
}


if(!isset($_SESSION['csrf_token'])){
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    "csrfToken" => $_SESSION['csrf_token']
]);

error_log("CSRF SESSION:" . $_SESSION['csrf_token']);