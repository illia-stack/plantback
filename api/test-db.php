<?php
require_once __DIR__ . '/../db.php';

try {
    $stmt = $conn->query("SELECT NOW() as now");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "DB connected! Server time: " . $row['now'];
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage();
}