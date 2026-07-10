<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header("Content-Type: application/json");

// 🔐 Alle Session-Daten löschen
$_SESSION = [];

// 🔐 Session-Cookie löschen (sehr wichtig!)
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

// 🔐 Session zerstören
session_destroy();

// 🔐 Neue frische Session starten
session_start();
session_regenerate_id(true);

// 🔐 Neuer CSRF Token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo json_encode([
  "success" => true,
  "csrfToken" => $_SESSION['csrf_token']
]);