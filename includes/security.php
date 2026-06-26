<?php 

function validate_csrf(){
    if(function_exists('getallheaders')){
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    } else { $headers = []; }

    $token = $headers['x-csrf-token'] ?? '';
    
    if(!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']){
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "CSRF failed"]);
        exit();
    }
}