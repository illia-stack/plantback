<?php

require_once __DIR__ . '/../includes/bootstrap.php';
header("Access-Control-Allow-Origin: https://plantfront.onrender.com");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    http_response_code(200);
    exit();
}

if(!isset($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    "csrfToken" => $_SESSION['csrf_token']
]);