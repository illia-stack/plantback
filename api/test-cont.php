<?php

header('Content-Type: application/json');

echo json_encode([
    "method" => $_SERVER["REQUEST_METHOD"],
    "raw_input" => file_get_contents("php://input")
]);