<?php
require_once "../includes/bootstrap.php";
require_once __DIR__ . '/../includes/db.php';


header('Content-Type: application/json');

$sql = "SELECT 
            id,
            name,
            description,
            price,
            image_url,
            category
        FROM products";

try {
    $stmt = $conn->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database query failed",
        "details" => $e->getMessage()
    ]);
}
?>