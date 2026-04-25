<?php
header('Content-Type: application/json');

// ❗ WICHTIG: Keine HTML Errors mehr!
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 🔴 Output Buffer verhindert kaputtes JSON
ob_start();

try {

    require_once __DIR__ . '/config.php';

    $raw = file_get_contents("php://input");

    if (!$raw) {
        throw new Exception("No input received");
    }

    $data = json_decode($raw);

    if (!$data) {
        throw new Exception("Invalid JSON input");
    }

    $lang = $data->language ?? 'en';

    if (!isset($data->cart)) {
        throw new Exception("Cart is missing");
    }

    $delivery = $data->delivery ?? (object)[];
    $cart = $data->cart;
    $user = $data->user ?? null;

    $line_items = [];

    foreach ($cart as $item) {

        if (!isset($item->name, $item->price, $item->quantity)) {
            throw new Exception("Invalid cart item structure");
        }

        $price = floatval($item->price);
        $quantity = intval($item->quantity);

        if ($price <= 0 || $quantity <= 0) {
            throw new Exception("Invalid price or quantity");
        }

        $line_items[] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $item->name,
                ],
                'unit_amount' => intval($price * 100),
            ],
            'quantity' => $quantity,
        ];
    }

    // Parameter eingeben und Stripe Session erstellen
    $sessionParams = [
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'locale' => $lang,
        'success_url' => 'https://plantfront.onrender.com/success',
        'cancel_url' => 'https://plantfront.onrender.com/cancel',

        'metadata' => [
            'name' => $delivery->name,
            'address' => $delivery->address,
            'city' => $delivery->city,
            'postal' => $delivery->postal,
            'country' => $delivery->country,
            'email' => $delivery->email,
            'phone' => $delivery->phone,

            'user_id' => $user->id ?? null // Fuer den Rabatt
        ]
    ];

    if ($user && isset($user->id)) {
    $sessionParams['discounts'] = [[
        'coupon' => 'AUTO_5_PERCENT'
    ]];
}

$session = \Stripe\Checkout\Session::create($sessionParams);

   
// 🔴 ALLES was vorher kam löschen (z.B. Warnings)
    ob_clean();

    echo json_encode([
        "url" => $session->url
    ]);

} catch (Exception $e) {

    ob_clean();

    http_response_code(500);

    echo json_encode([
        "error" => $e->getMessage()
    ]);
}

exit;