<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/../includes/db.php";

// ----------------------
// GET COMMENTS
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $product_id = $_GET['product_id'] ?? null;

    if (!$product_id) {
        echo json_encode([]);
        exit();
    }

    try {
        $stmt = $conn->prepare("
            SELECT * FROM comments 
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
    $username = trim($data['username']);
    $comment = trim($data['comment']);

    if ($username === "" || $comment === "") {
        echo json_encode(["success" => false, "error" => "Empty fields"]);
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