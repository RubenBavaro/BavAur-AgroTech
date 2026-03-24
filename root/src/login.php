<?php
require_once 'config/db.php';
require_once 'config/session.php'; // Sessione solo qui

// Se già loggato → redirect
if (isLoggedIn()) {
    redirect(in_array($_SESSION['user']['ruolo'],['superadmin','sede_admin']) ? 'index.php' : 'homepage.php');
}

$error = '';
$mode  = $_POST['mode'] ?? 'user'; // 'user' o 'sede'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $mode     = $_POST['mode'] ?? 'user';

    if (!$email || !$password) {
        $error = 'Inserisci email e password.';
    } elseif ($mode === 'sede') {
        // ── Login amministratore sede ────────────────────────
        $stmt = $pdo->prepare("SELECT * FROM UTENTE WHERE email=? AND ruolo='sede_admin' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Carica i dati della sede
            $sede = $pdo->prepare("SELECT * FROM SEDE WHERE idSede=?");
            $sede->execute([$user['idSede']]);
            $sedeRow = $sede->fetch();

            $_SESSION['user'] = [
                'idUtente' => $user['idUtente'],
                'nome'     => $user['nome'],
                'email'    => $user['email'],
                'ruolo'    => 'sede_admin',
                'idSede'   => $user['idSede'],
                'nomeSede' => $sedeRow['nomeSede'] ?? '',
            ];
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);
        } else {
            $error = 'Credenziali sede non valide.';
        }
    } else {
        // ── Login utente normale / superadmin ────────────────
        $stmt = $pdo->prepare("SELECT * FROM UTENTE WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'idUtente' => $user['idUtente'],
                'nome'     => $user['nome'],
                'email'    => $user['email'],
                'ruolo'    => $user['ruolo'],
                'idSede'   => $user['idSede'],
            ];
            // Clienti → homepage
            if ($user['ruolo'] === 'cliente') {
                redirect('homepage.php');
            }
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);
        } else {
            $error = 'Email o password non corretti.';
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — AgroManager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Theme toggle floating -->
<button class="theme-toggle" id="themeToggle" style="position:fixed;top:16px;right:16px;z-index:9999">
  <i class="fa-solid fa-moon" id="themeIcon"></i>
</button>

<div class="auth-page">
  <div style="width:100%;max-width:460px">

    <!-- Back to homepage -->
    <div class="text-center mb-3">
      <a href="homepage.php" style="font-size:.85rem;color:var(--ag-text-muted)">
        <i class="fa-solid fa-arrow-left me-1"></i> Torna alla homepage
      </a>
    </div>

    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-logo">🌿</div>
        <div class="auth-title">AgroManager</div>
        <div class="auth-sub">Accedi al tuo account</div>
      </div>
      <div class="auth-body">

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> mb-3" role="alert">
          <i class="fa-solid fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?> me-2"></i>
          <?= h($flash['msg']) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger mb-3" role="alert">
          <i class="fa-solid fa-exclamation-circle me-2"></i><?= h($error) ?>
        </div>
        <?php endif; ?>

        <!-- Mode tabs -->
        <div class="d-flex gap-2 mb-4 p-1" style="background:var(--ag-very-pale);border-radius:12px;border:1px solid var(--ag-border)">
          <button type="button" class="flex-fill py-2 border-0 rounded-3 fw-semibold" id="tabUser"
                  style="font-size:.88rem;transition:all .2s;cursor:pointer">
            <i class="fa-solid fa-user me-1"></i> Utente
          </button>
          <button type="button" class="flex-fill py-2 border-0 rounded-3 fw-semibold" id="tabSede"
                  style="font-size:.88rem;transition:all .2s;cursor:pointer">
            <i class="fa-solid fa-location-dot me-1"></i> Admin Sede
          </button>
        </div>

        <form method="POST" action="login.php">
          <input type="hidden" name="mode" id="modeInput" value="<?= h($mode) ?>">

          <div class="mb-3">
            <label class="form-label">Email</label>
            <div style="position:relative">
              <i class="fa-solid fa-envelope" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--ag-text-muted);font-size:.85rem"></i>
              <input type="email" name="email" class="form-control" style="padding-left:36px"
                     value="<?= h($_POST['email'] ?? '') ?>"
                     placeholder="nome@esempio.it" required autofocus>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Password</label>
            <div style="position:relative">
              <i class="fa-solid fa-lock" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--ag-text-muted);font-size:.85rem"></i>
              <input type="password" name="password" id="passwordInput" class="form-control" style="padding-left:36px"
                     placeholder="••••••••" required>
              <button type="button" id="togglePwd" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--ag-text-muted);cursor:pointer;font-size:.85rem">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>

          <!-- Sede admin hint -->
          <div id="sedeHint" class="mb-3 p-3 d-none" style="background:var(--ag-pale);border-radius:10px;border:1px solid var(--ag-border);font-size:.82rem;color:var(--ag-primary)">
            <i class="fa-solid fa-circle-info me-1"></i>
            Le credenziali dell'amministratore sede vengono impostate dall'amministratore di sistema via phpMyAdmin.
          </div>

          <button type="submit" class="btn-ag w-100" style="justify-content:center;padding:12px">
            <i class="fa-solid fa-right-to-bracket me-1"></i> Accedi
          </button>
        </form>

        <hr style="margin:20px 0;border-color:var(--ag-border)">

        <div class="text-center" style="font-size:.85rem;color:var(--ag-text-muted)">
          Non hai un account?
          <a href="register.php" style="font-weight:600">Registrati</a>
        </div>

        <!-- Test credentials hint (dev only) -->
        <div class="mt-3 p-3" style="background:var(--ag-very-pale);border-radius:10px;border:1px solid var(--ag-border);font-size:.78rem;color:var(--ag-text-muted)">
          <strong style="color:var(--ag-primary)">🔑 Credenziali di test:</strong><br>
          Superadmin: <code>superadmin@agro.it</code> / <code>admin123</code><br>
          Sede Nord: <code>nord@agro.it</code> / <code>sede123</code> (tab Admin Sede)<br>
          Cliente: <code>mario@email.it</code> / <code>cliente123</code>
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

// Tabs
const tabUser = document.getElementById('tabUser');
const tabSede = document.getElementById('tabSede');
const modeInput = document.getElementById('modeInput');
const sedeHint  = document.getElementById('sedeHint');
let currentMode = '<?= h($mode) ?>';

function setTab(mode) {
  currentMode = mode;
  modeInput.value = mode;
  const activeStyle  = 'background:var(--ag-primary);color:#fff;';
  const inactiveStyle = 'background:transparent;color:var(--ag-text-muted);';
  tabUser.style.cssText = mode==='user'  ? activeStyle : inactiveStyle;
  tabSede.style.cssText = mode==='sede' ? activeStyle : inactiveStyle;
  sedeHint.classList.toggle('d-none', mode !== 'sede');
}
setTab(currentMode);
tabUser.addEventListener('click', () => setTab('user'));
tabSede.addEventListener('click', () => setTab('sede'));

// Toggle password visibility
document.getElementById('togglePwd').addEventListener('click', () => {
  const inp = document.getElementById('passwordInput');
  const ico = document.querySelector('#togglePwd i');
  inp.type  = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
});
</script>
</body>
</html>
