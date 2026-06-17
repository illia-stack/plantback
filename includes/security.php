<?php 

function validate_csrf(){
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
    
    if(!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']){
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "CSRF failed"]);
        exit();
    }
}