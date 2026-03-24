<?php
require_once 'config/session.php';

// Distrugge completamente la sessione
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']
    );
}
session_destroy();

// Redirect alla homepage
header('Location: homepage.php');
exit;
