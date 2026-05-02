<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $stmt = $conn->query("SELECT NOW() AS now");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "DB connected! Server time: " . $row['now'];
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage();
}