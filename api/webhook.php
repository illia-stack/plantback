<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'db.php';



$stripeSecretKey = getenv('STRIPE_SECRET_KEY');
\Stripe\Stripe::setApiKey($stripeSecretKey);

$endpoint_secret = getenv('STRIPE_WEBHOOK_SECRET');

if (!$endpoint_secret) {
    http_response_code(500);
    echo 'Webhook secret not found.';
    exit();
}

// Get request data
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {

    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );

    if ($event->type === 'checkout.session.completed') {

        $session = $event->data->object;
        $sessionId = $session->id;

        // Retrieve full session with line items
        $session = \Stripe\Checkout\Session::retrieve([
            'id' => $sessionId,
            'expand' => ['line_items']
        ]);

        $customer_name = $session->metadata->name ?? '';
        $address = $session->metadata->address ?? '';
        $city = $session->metadata->city ?? '';
        $postal = $session->metadata->postal ?? '';
        $country = $session->metadata->country ?? '';
        $email = $session->metadata->email ?? '';
        $phone = $session->metadata->phone ?? '';

        foreach ($session->line_items->data as $item) {

            $name = $item->description;
            $quantity = $item->quantity;
            $price = $item->amount_total / 100;
            $total = $price * $quantity;

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
                ':session_id' => $sessionId,
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
        }
    }

    http_response_code(200);

} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    echo 'Webhook signature verification failed.';
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error processing webhook: ' . $e->getMessage();
    exit();
}
?>