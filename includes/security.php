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


    function rate_limit($key, $maxRequests = 5, $perSeconds = 60) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0];
        $identifier = $key . '_' . $ip;

        $file = sys_get_temp_dir() . "/rate_limit_" . md5($identifier);

        $data = [
            'count' => 0,
            'start' => time()
        ];

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
        }

        // Reset window
        if (time() - $data['start'] > $perSeconds) {
            $data = [
                'count' => 0,
                'start' => time()
            ];
        }

        $data['count']++;

        file_put_contents($file, json_encode($data));

        if ($data['count'] > $maxRequests) {
            http_response_code(429);
            echo json_encode([
                "success" => false,
                "message" => "Too many requests. Please try again later."
            ]);
            exit();
        }
    }
?>