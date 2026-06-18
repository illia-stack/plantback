<?php

//Cookie Settings
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => true,
    'samesite' => 'None'
]);
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
