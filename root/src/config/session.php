<?php
// ── Session helper ───────────────────────────────────────────
// Avvia la sessione SOLO se non è già attiva.
// Da includere esplicitamente nelle pagine che ne hanno bisogno.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Restituisce l'utente loggato dalla sessione
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

function isSuperAdmin(): bool {
    return ($_SESSION['user']['ruolo'] ?? '') === 'superadmin';
}

function isSedeAdmin(): bool {
    return ($_SESSION['user']['ruolo'] ?? '') === 'sede_admin';
}

function isAdmin(): bool {
    return in_array($_SESSION['user']['ruolo'] ?? '', ['superadmin', 'sede_admin']);
}

// Restituisce l'idSede dell'utente (NULL per superadmin)
function userSede(): ?int {
    return $_SESSION['user']['idSede'] ?? null;
}
