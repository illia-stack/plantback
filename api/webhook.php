<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php'; // DB Pfad prüfen!

// 🔥 Debug aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

$stripeSecretKey = getenv('STRIPE_SECRET_KEY');
\Stripe\Stripe::setApiKey($stripeSecretKey);

$endpoint_secret = getenv('STRIPE_WEBHOOK_SECRET');

if (!$endpoint_secret) {
    http_response_code(500);
    error_log("Webhook secret missing");
    exit();
}

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );

    error_log("Webhook event type: " . $event->type);

    if ($event->type === 'checkout.session.completed') {

        $session = $event->data->object;

        // Prüfen ob Line Items expandiert werden müssen
        $session = \Stripe\Checkout\Session::retrieve($session->id, [
            'expand' => ['line_items.data.price.product']
        ]);

        // Log: Metadaten
        error_log("Customer metadata: " . json_encode($session->metadata));
        error_log("Number of line items: " . count($session->line_items->data));

        foreach ($session->line_items->data as $item) {
            $name = $item->price->product->name ?? $item->description ?? 'Unnamed';
            $quantity = $item->quantity ?? 0;

            // Berechnung
            $total = $item->amount_total / 100 ?? 0;
            $price = $quantity > 0 ? $total / $quantity : 0;

            $customer_name = $session->metadata['name'] ?? '';
            $address       = $session->metadata['address'] ?? '';   
            $city          = $session->metadata['city'] ?? '';
            $postal        = $session->metadata['postal'] ?? '';
            $country       = $session->metadata['country'] ?? '';
            $email         = $session->metadata['email'] ?? '';
            $phone         = $session->metadata['phone'] ?? '';

            // Log: Daten vor DB-Insert
            error_log("Inserting sale: " . json_encode([
                'name' => $name,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total
            ]));

            try {
                $stmt = $conn->prepare("
                    INSERT INTO sales (
                        stripe_session_id,
                        product_name,
                        quantity,
                        price,
                        total,
                        customer_name,
                        address,
                        city,
                        postal,
                        country,
                        email,
                        phone,
                        sale_date
                    )
                    VALUES (
                        :session_id, :name, :quantity, :price, :total,
                        :customer_name, :address, :city, :postal, :country,
                        :email, :phone, NOW()
                    )
                ");

                $stmt->execute([
                    ':session_id' => $session->id,
                    ':name' => $name,
                    ':quantity' => $quantity,
                    ':price' => $price,
                    ':total' => $total,
                    ':customer_name' => $customer_name,
                    ':address' => $address,
                    ':city' => $city,
                    ':postal' => $postal,
                    ':country' => $country,
                    ':email' => $email,
                    ':phone' => $phone
                ]);

                error_log("✅ Sale inserted: $name ($quantity x $price €)");
            } catch (PDOException $e) {
                error_log("❌ PDO Error: " . $e->getMessage());
            }
        }

    }

    http_response_code(200);

} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    error_log("❌ Signature verification failed: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    error_log("❌ General error: " . $e->getMessage());
}