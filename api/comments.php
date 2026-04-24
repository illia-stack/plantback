<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/../includes/db.php";

// ----------------------
// GET COMMENTS
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

    if ($product_id <= 0) {
        echo json_encode([]);
        exit();
}

    try {
        $stmt = $conn->prepare("
            SELECT id, username, comment, created_at
            FROM comments 
            WHERE product_id = :product_id 
            ORDER BY created_at DESC
        ");
        $stmt->execute(['product_id' => $product_id]);

        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($comments);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }

    exit();
}

// ----------------------
// POST COMMENT
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data['product_id'], $data['username'], $data['comment'])) {
        echo json_encode(["success" => false, "error" => "Invalid input"]);
        exit();
    }

    $product_id = (int)$data['product_id'];
    $username = htmlspecialchars(trim($data['username']), ENT_QUOTES, 'UTF-8');
    $comment = htmlspecialchars(trim($data['comment']), ENT_QUOTES, 'UTF-8');

    if ($username === "" || $comment === "") {
        echo json_encode(["success" => false, "error" => "Empty fields"]);
        exit();
    }

    if ($product_id <= 0) {
        echo json_encode(["success" => false, "error" => "Invalid product_id"]);
        exit();
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO comments (product_id, username, comment) 
            VALUES (:product_id, :username, :comment)
        ");

        $stmt->execute([
            'product_id' => $product_id,
            'username' => $username,
            'comment' => $comment
        ]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }

    exit();
}