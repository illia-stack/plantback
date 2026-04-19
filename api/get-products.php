<?php
require '../includes/db.php';

header('Access-Control-Allow-Origin: https://plant-shop-frontend.onrender.com');  // Sicherstellen, dass Cross-Origin-Anfragen erlaubt sind
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Falls der Server eine OPTIONS-Anfrage erhält, eine 200-Response zurückgeben
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Sprache aus der URL abrufen (z.B. ?lang=es für Spanisch)
$lang = $_GET['lang'] ?? 'en';  // Standard ist Englisch, falls keine Sprache angegeben wird

// Abhängig von der Sprache unterschiedliche SQL-Abfragen für Produktnamen und Beschreibungen
switch ($lang) {
    case 'es':  // Spanisch
        $sql = "SELECT 
                    id,
                    name_es AS name,
                    description_es AS description,
                    price,
                    image_url,
                    category_es AS category
                FROM products";
        break;

    case 'de':  // Deutsch
        $sql = "SELECT 
                    id,
                    name_de AS name,
                    description_de AS description,
                    price,
                    image_url,
                    category_de AS category
                FROM products";
        break;

    default:  // Englisch (Standard)
        $sql = "SELECT 
                    id,
                    name,
                    description,
                    price,
                    image_url,
                    category
                FROM products";
        break;
}

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