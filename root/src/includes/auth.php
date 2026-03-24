<?php
// ── Auth Guard ───────────────────────────────────────────────
// Includere in cima a ogni pagina protetta (solo area admin).
// Avvia la sessione e blocca gli accessi non autorizzati.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    flash('error', 'Devi effettuare il login per accedere a questa pagina.');
    redirect('/login.php');
}

// Clienti → homepage (non possono stare nell'area admin)
if (($_SESSION['user']['ruolo'] ?? '') === 'cliente') {
    redirect('/homepage.php');
}