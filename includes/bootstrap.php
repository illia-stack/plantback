<?php

error_log("BOOTSTRAP LOADED");

header("Access-Control-Allow-Origin: https://plantfront.onrender.com");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");
header("Access-Control-Allow-Credentials: true");

// Preflight Request abfangen
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

//Cookie Settings
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'plantback.onrender.com',
    'httponly' => true,
    'secure' => true,
    'samesite' => 'None'
]);
session_start();
require_once __DIR__ . '/security.php';
