<?php
    require_once __DIR__ . '/../includes/bootstrap.php';

    header("Content-Type: application/json");

    // 🔐 Delete all Session-Daten
    $_SESSION = [];

    // 🔐 Delete the Session-Cookie 
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // 🔐 End Session 
    session_destroy();

    // 🔐 Start a new Session
    session_start();
    session_regenerate_id(true);

    // 🔐 New CSRF Token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    echo json_encode([
    "success" => true,
    "csrfToken" => $_SESSION['csrf_token']
    ]);
?>