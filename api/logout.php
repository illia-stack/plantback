<?php
require_once __DIR__ . '/../includes/bootstrap.php';

session_destroy();

echo json_encode(["success" => true]);