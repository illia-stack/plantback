<?php
header('Access-Control-Allow-Origin: https://plant-shop-frontend.onrender.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// ✅ ONLY load .env if file exists (local dev)
$envPath = __DIR__ . '/../.env';

if (file_exists($envPath)) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// ✅ Read from environment (Render provides this)
$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: ($_ENV['STRIPE_SECRET_KEY'] ?? null);

if (!$stripeSecretKey) {
    throw new Exception("Stripe secret key not found");
}

\Stripe\Stripe::setApiKey($stripeSecretKey);