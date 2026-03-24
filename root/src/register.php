<?php
require_once 'config/db.php';
require_once 'config/session.php';

if (isLoggedIn()) {
    redirect(in_array($_SESSION['user']['ruolo'],['superadmin','sede_admin']) ? 'index.php' : 'homepage.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome    = trim($_POST['nome'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $pw      = $_POST['password'] ?? '';
    $pw2     = $_POST['password2'] ?? '';

    if (!$nome || !$email || !$pw || !$pw2) {
        $error = 'Compila tutti i campi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email non valida.';
    } elseif (strlen($pw) < 8) {
        $error = 'La password deve essere di almeno 8 caratteri.';
    } elseif ($pw !== $pw2) {
        $error = 'Le password non coincidono.';
    } else {
        // Controlla duplicato email
        $check = $pdo->prepare("SELECT idUtente FROM UTENTE WHERE email=?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Questa email è già registrata.';
        } else {
            $hash = password_hash($pw, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO UTENTE (nome, email, password_hash, ruolo) VALUES (?,?,?,'cliente')")
                ->execute([$nome, $email, $hash]);
            flash('success', 'Account creato con successo! Effettua il login.');
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registrazione — AgroManager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<button class="theme-toggle" id="themeToggle" style="position:fixed;top:16px;right:16px;z-index:9999">
  <i class="fa-solid fa-moon" id="themeIcon"></i>
</button>

<div class="auth-page">
  <div style="width:100%;max-width:460px">

    <div class="text-center mb-3">
      <a href="homepage.php" style="font-size:.85rem;color:var(--ag-text-muted)">
        <i class="fa-solid fa-arrow-left me-1"></i> Torna alla homepage
      </a>
    </div>

    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-logo">🌿</div>
        <div class="auth-title">Crea Account</div>
        <div class="auth-sub">Registrati gratuitamente ad AgroManager</div>
      </div>
      <div class="auth-body">

        <?php if ($error): ?>
        <div class="alert alert-danger mb-3">
          <i class="fa-solid fa-exclamation-circle me-2"></i><?= h($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
          <div class="mb-3">
            <label class="form-label">Nome e Cognome</label>
            <div style="position:relative">
              <i class="fa-solid fa-user" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--ag-text-muted);font-size:.85rem"></i>
              <input type="text" name="nome" class="form-control" style="padding-left:36px"
                     value="<?= h($_POST['nome'] ?? '') ?>" placeholder="Mario Rossi" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <div style="position:relative">
              <i class="fa-solid fa-envelope" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--ag-text-muted);font-size:.85rem"></i>
              <input type="email" name="email" class="form-control" style="padding-left:36px"
                     value="<?= h($_POST['email'] ?? '') ?>" placeholder="mario@esempio.it" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Password <small class="text-muted">(min. 8 caratteri)</small></label>
            <div style="position:relative">
              <i class="fa-solid fa-lock" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--ag-text-muted);font-size:.85rem"></i>
              <input type="password" name="password" id="pw1" class="form-control" style="padding-left:36px"
                     placeholder="••••••••" required minlength="8">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Conferma Password</label>
            <div style="position:relative">
              <i class="fa-solid fa-lock" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--ag-text-muted);font-size:.85rem"></i>
              <input type="password" name="password2" id="pw2" class="form-control" style="padding-left:36px"
                     placeholder="••••••••" required minlength="8" oninput="checkPw()">
              <small id="pwMatch" style="font-size:.75rem;margin-top:4px;display:block"></small>
            </div>
          </div>

          <!-- Info box -->
          <div class="mb-4 p-3" style="background:var(--ag-very-pale);border-radius:10px;border:1px solid var(--ag-border);font-size:.78rem;color:var(--ag-text-muted)">
            <i class="fa-solid fa-circle-info me-1" style="color:var(--ag-primary)"></i>
            Registrandoti otterrai un account <strong>Cliente</strong>. Per accedere al pannello di amministrazione contatta il tuo responsabile.
          </div>

          <button type="submit" class="btn-ag w-100" style="justify-content:center;padding:12px">
            <i class="fa-solid fa-user-plus me-1"></i> Crea Account
          </button>
        </form>

        <hr style="margin:20px 0;border-color:var(--ag-border)">
        <div class="text-center" style="font-size:.85rem;color:var(--ag-text-muted)">
          Hai già un account? <a href="login.php" style="font-weight:600">Accedi</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Theme
const html = document.documentElement;
function applyTheme(t) {
  html.setAttribute('data-theme', t);
  document.getElementById('themeIcon').className = t==='dark'?'fa-solid fa-sun':'fa-solid fa-moon';
  localStorage.setItem('ag_theme', t);
}
applyTheme(localStorage.getItem('ag_theme') || 'light');
document.getElementById('themeToggle').addEventListener('click', () => {
  applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark');
});

// Password match check
function checkPw() {
  const p1 = document.getElementById('pw1').value;
  const p2 = document.getElementById('pw2').value;
  const el = document.getElementById('pwMatch');
  if (!p2) { el.textContent = ''; return; }
  if (p1 === p2) {
    el.innerHTML = '<span style="color:#16a34a"><i class="fa-solid fa-check me-1"></i>Le password coincidono</span>';
  } else {
    el.innerHTML = '<span style="color:#dc2626"><i class="fa-solid fa-xmark me-1"></i>Le password non coincidono</span>';
  }
}
</script>
</body>
</html>
