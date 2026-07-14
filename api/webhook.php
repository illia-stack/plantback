<?php

    require_once __DIR__ . '/../includes/bootstrap.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../vendor/autoload.php';


    header("Content-Type: text/plain");


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

                        

            // ✅ Call all the lines
            $lineItemsObj = \Stripe\Checkout\Session::allLineItems($session->id, ['limit' => 100]);

            if (empty($lineItemsObj->data)) {
                error_log("⚠️ Line items empty!");
            } 

            foreach ($lineItemsObj->data as $item) {
                $name = $item->description ?? 'Unnamed';
                $quantity = $item->quantity ?? 0;
                $unit_price = ($item->price->unit_amount ?? 0) / 100;
                $total = $unit_price * $quantity;

                $customer_name = $session->metadata['name'] ?? '';
                $address       = $session->metadata['address'] ?? '';   
                $city          = $session->metadata['city'] ?? '';
                $postal        = $session->metadata['postal'] ?? '';
                $country       = $session->metadata['country'] ?? '';
                $email         = $session->metadata['email'] ?? '';
                $phone         = $session->metadata['phone'] ?? '';


                // 🔹 Log: Data before DB-Insert
                error_log("Inserting sale: " . json_encode([
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
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
                        ':price' => $unit_price,
                        ':total' => $total,
                        ':customer_name' => $customer_name,
                        ':address' => $address,
                        ':city' => $city,
                        ':postal' => $postal,
                        ':country' => $country,
                        ':email' => $email,
                        ':phone' => $phone
                    ]);

                    error_log("✅ Sale inserted: $name ($quantity x $unit_price €)");
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        error_log("Duplicate webhook ignored: " . $session->id);
                    } else {
                        error_log("❌ PDO Error: " . $e->getMessage());
                        throw $e;
                    }
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
?>