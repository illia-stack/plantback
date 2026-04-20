<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

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

    $stmt = $conn->prepare("
        SELECT * FROM comments 
        WHERE product_id = ? 
        ORDER BY created_at DESC
    ");

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $comments = [];

    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }

    echo json_encode($comments);
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

    $stmt = $conn->prepare("
        INSERT INTO comments (product_id, username, comment) 
        VALUES (?, ?, ?)
    ");

    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "Prepare failed"]);
        exit();
    }

    $stmt->bind_param("iss", $product_id, $username, $comment);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    exit();
}