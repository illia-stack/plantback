<?php
require_once __DIR__ . '/db.php';

try {
    $stmt = $conn->prepare("INSERT INTO sales (stripe_session_id, product_name, quantity, price, total) VALUES ('TEST123', 'TestPlant', 1, 10.00, 10.00)");
    $stmt->execute();
    echo "✅ Test Insert success!";
} catch (Exception $e) {
    echo "❌ Test Insert failed: " . $e->getMessage();
}
?>