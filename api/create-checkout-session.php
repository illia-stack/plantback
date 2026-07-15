<?php
    ini_set('display_errors', 1);
error_reporting(E_ALL);
    require_once __DIR__ . '/../includes/bootstrap.php';
    require_once __DIR__ . '/config.php';
        
    header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");
    header("Access-Control-Allow-Credentials: true");
    header('Content-Type: application/json');


    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    // 🔴 Output Buffer prevents a wrong JSON
    ob_start();

    try {
        validate_csrf();



        $raw = file_get_contents("php://input");

        if (!$raw) {
            throw new Exception("No input received");
        }



        $data = json_decode($raw, true);

        if (!$data) {
            throw new Exception("Invalid JSON input");
        }

        if (!isset($data['cart'])) {
            throw new Exception("Cart is missing");
        }

        $delivery = $data['delivery'] ?? [];
        $cart = $data['cart'];


        if (empty($cart)) {
            throw new Exception("Cart is empty");
        }



        $user = $_SESSION['user'] ?? null;


        

        $line_items = [];




        foreach ($cart as $item) {

            if (!isset($item['id'], $item['quantity'])) {
                throw new Exception("Invalid cart item");
            }

            $product = getProductById($item['id']);

            if (!$product) {
                throw new Exception("Product not found");
            }

            $price = floatval($product['price']);
            $quantity = intval($item['quantity']);

            // ✅ Apply discount safely on backend
            if ($user && isset($user['id'])) {
                $price = round($price * 0.95, 2);
            }

            if ($price <= 0 || $quantity <= 0) {
                throw new Exception("Invalid price or quantity");
            }

            $line_items[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $product['name'],
                    ],
                    'unit_amount' => intval($price * 100),
                ],
                'quantity' => $quantity,
            ];
        }

        
        

        // Give a parameter and create a Stripe Session 
        $sessionParams = [
            'payment_method_types' => ['card'],
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => 'https://plantfront.onrender.com/success',
            'cancel_url' => 'https://plantfront.onrender.com/cancel',
            'customer_email' => $delivery->['email'] ?? null,

            'metadata' => array_filter([
                'name' => $delivery->['name'] ?? '',
                'address' => $delivery->['address'] ?? '',
                'city' => $delivery->['city'] ?? '',
                'postal' => $delivery->['postal'] ?? '',
                'country' => $delivery->['country'] ?? '',
                'email' => $delivery->['email'] ?? null,
                'phone' => $delivery->['phone'] ?? '',
                'user_id' => $user['id'] ?? ''
            ])
        ];

        

        $session = \Stripe\Checkout\Session::create($sessionParams);

        ob_clean();

        echo json_encode([
            "url" => $session->url
        ]);

    } catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage() // 👈 show real error
    ]);
}
    

    exit;

?>