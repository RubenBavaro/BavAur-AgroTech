<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$ruolo = $_SESSION['user']['ruolo'] ?? null;
if ($ruolo === 'superadmin' || $ruolo === 'sede_admin') {
    header('Location: dashboard.php');
} else {
    header('Location: homepage.php');
}
exit;