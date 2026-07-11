<?php

header("Access-Control-Allow-Origin: https://plantfront.onrender.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

echo json_encode([
    "method" => $_SERVER["REQUEST_METHOD"],
    "raw_input" => file_get_contents("php://input")
]);