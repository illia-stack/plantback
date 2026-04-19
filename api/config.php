<?php
// CORS headers
header('Access-Control-Allow-Origin: https://plant-shop-frontend.onrender.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// Now load Composer and Stripe
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../', '.env');
$dotenv->load();

// Stripe key
$stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
if (!empty($_ENV['STRIPE_SECRET_KEY'])) {
    \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
}

\Stripe\Stripe::setApiKey($stripeSecretKey);
?>