<?php

    require_once __DIR__ . '/../includes/bootstrap.php';

    header('Content-Type: application/json');

    require_once __DIR__ . '/../vendor/autoload.php';

    use Dotenv\Dotenv;

    // ✅ Read from environment (Render provides this)
    $stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: ($_ENV['STRIPE_SECRET_KEY'] ?? null);

    if (!$stripeSecretKey) {
        throw new Exception("Stripe secret key not found");
    }

    \Stripe\Stripe::setApiKey($stripeSecretKey);
?>