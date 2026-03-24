<?php
require_once 'config/db.php';
require_once 'config/session.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['ruolo'] !== 'cliente') {
    header('Location: login.php'); exit;
}

$userId   = $_SESSION['user']['idUtente'];
$userName = $_SESSION['user']['nome'];

// Svuota il carrello se c'è un ordine appena confermato (lo facciamo via JS)
$lastOrder = $_SESSION['last_order'] ?? null;
unset($_SESSION['last_order']);

$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

// Trova il cliente anagrafico di questo utente
$stmtCli = $pdo->prepare("SELECT idCliente FROM CLIENTE WHERE contatti LIKE ?");
$stmtCli->execute(['%utente:'.$userId.'%']);
$cliRow   = $stmtCli->fetch();
$idCliente = $cliRow['idCliente'] ?? null;

$ordini = [];
if ($idCliente) {
    $ordini = $pdo->prepare("
        SELECT v.*, s.nomeSede,
               COUNT(d.idDettaglio) AS nRighe
        FROM VENDITA v
        JOIN SEDE s ON v.idSede = s.idSede
        LEFT JOIN DETTAGLIO_VENDITA d ON d.idVendita = v.idVendita
        WHERE v.idCliente = ?
        GROUP BY v.idVendita
        ORDER BY v.dataVendita DESC, v.idVendita DESC");
    $ordini->execute([$idCliente]);
    $ordini = $ordini->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>I Miei Ordini — AgroManager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <script>(function(){ var t=localStorage.getItem('ag_theme')||'light'; document.documentElement.setAttribute('data-theme',t); })();</script>
</head>
<body>

<nav class="pub-nav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="homepage.php" class="pub-nav-brand text-decoration-none"><span>🌿</span> AgroManager</a>
    <div class="pub-nav-actions">
      <button class="theme-toggle" id="themeToggle"><i class="fa-solid fa-moon" id="themeIcon"></i></button>
      <a href="carrello.php" class="btn-ag-outline btn-ag-sm" style="color:#fff;border-color:rgba(255,255,255,.4)">
        <i class="fa-solid fa-basket-shopping"></i> Carrello
      </a>
      <a href="logout.php" class="btn-ag-outline btn-ag-sm" style="color:#fff;border-color:rgba(255,255,255,.4)">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
      </a>
    </div>
  </div>
</nav>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show m-0" style="border-radius:0">
  <div class="container"><?= h($flash['msg']) ?> <button class="btn-close" data-bs-dismiss="alert"></button></div>
</div>
<?php endif; ?>

<div class="container" style="padding:32px 16px;max-width:900px">

  <nav class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="homepage.php">Home</a></li>
      <li class="breadcrumb-item active">I Miei Ordini</li>
    </ol>
  </nav>

  <!-- Banner ultimo ordine -->
  <?php if ($lastOrder): ?>
  <div class="mb-4 p-4" style="background:linear-gradient(135deg,var(--ag-primary),var(--ag-medium));color:#fff;border-radius:18px;box-shadow:var(--ag-shadow-lg)">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <div style="font-size:2.5rem">🎉</div>
      <div style="flex:1">
        <div style="font-size:1.15rem;font-weight:800">Ordine #<?= $lastOrder['idVendita'] ?> confermato!</div>
        <div style="opacity:.85;font-size:.9rem">
          <?= $lastOrder['nProdotti'] ?> prodott<?= $lastOrder['nProdotti']!=1?'i':'o' ?> &nbsp;·&nbsp;
          Totale: <strong>€<?= number_format($lastOrder['totale'],2,',','.') ?></strong>
        </div>
      </div>
      <a href="homepage.php#prodotti" class="btn-ag" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4)">
        <i class="fa-solid fa-basket-shopping"></i> Continua lo shopping
      </a>
    </div>
  </div>
  <?php endif; ?>

  <div class="page-header">
    <h5 class="page-header-title"><i class="fa-solid fa-receipt"></i> I Miei Ordini</h5>
    <a href="carrello.php" class="btn-ag"><i class="fa-solid fa-basket-shopping"></i> Nuovo Acquisto</a>
  </div>

  <?php if (empty($ordini)): ?>
  <div class="ag-card">
    <div class="empty-state">
      <i class="fa-solid fa-receipt"></i>
      <p>Non hai ancora effettuato ordini.<br>
        <a href="homepage.php#prodotti" class="btn-ag" style="display:inline-flex;margin-top:12px">
          <i class="fa-solid fa-seedling"></i> Scopri i prodotti
        </a>
      </p>
    </div>
  </div>
  <?php else: ?>
  <div class="ag-card">
    <div class="ag-card-header">
      <h6 class="ag-card-title"><i class="fa-solid fa-list"></i> Storico Ordini (<?= count($ordini) ?>)</h6>
    </div>
    <div style="padding:8px 0">
      <?php foreach ($ordini as $i => $o):
        // Dettagli ordine
        $dets = $pdo->prepare("
            SELECT dv.*, p.nome AS prodotto, p.tipoProdotto, p.unitaMisura
            FROM DETTAGLIO_VENDITA dv
            JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
            WHERE dv.idVendita = ?");
        $dets->execute([$o['idVendita']]);
        $dettagli = $dets->fetchAll();
      ?>
      <div style="padding:20px 24px;border-bottom:1px solid var(--ag-border);<?= $i===0?'':'margin-top:4px' ?>">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
          <div>
            <div style="font-weight:700;font-size:1rem;color:var(--ag-text)">
              Ordine #<?= $o['idVendita'] ?>
              <span class="badge-ok ms-2" style="font-size:.72rem">Completato</span>
            </div>
            <div style="font-size:.82rem;color:var(--ag-text-muted);margin-top:3px">
              <i class="fa-regular fa-calendar me-1"></i><?= date('d/m/Y', strtotime($o['dataVendita'])) ?>
              &nbsp;·&nbsp;
              <i class="fa-solid fa-location-dot me-1"></i><?= h($o['nomeSede']) ?>
              &nbsp;·&nbsp;
              <?= $o['nRighe'] ?> prodott<?= $o['nRighe']!=1?'i':'o' ?>
            </div>
          </div>
          <div class="total-box" style="padding:12px 18px;min-width:120px;text-align:right">
            <div class="label">Totale Pagato</div>
            <div class="value" style="font-size:1.2rem">€<?= number_format($o['totalePagato'],2,',','.') ?></div>
          </div>
        </div>

        <!-- Prodotti dell'ordine -->
        <div style="display:flex;flex-wrap:wrap;gap:8px">
          <?php foreach ($dettagli as $d): ?>
          <div style="background:var(--ag-very-pale);border:1px solid var(--ag-border);border-radius:10px;padding:8px 12px;font-size:.82rem">
            <span class="fw-semibold" style="color:var(--ag-text)"><?= h($d['prodotto']) ?></span>
            <span class="text-muted"> ×<?= $d['quantita'] ?></span>
            <span class="badge-<?= $d['tipoProdotto'] ?> ms-1" style="font-size:.68rem"><?= $d['tipoProdotto'] ?></span>
            <span style="color:var(--ag-primary);margin-left:6px;font-weight:600">€<?= number_format($d['prezzoUnitario']*$d['quantita'],2,',','.') ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ($o['note']): ?>
        <div style="margin-top:10px;font-size:.8rem;color:var(--ag-text-muted)">
          <i class="fa-solid fa-note-sticky me-1"></i><?= h($o['note']) ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

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
applyTheme(localStorage.getItem('ag_theme')||'light');
document.getElementById('themeToggle').addEventListener('click',()=>{
  applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark');
});

// Svuota il carrello dopo un ordine confermato
<?php if ($lastOrder): ?>
localStorage.removeItem('ag_cart');
<?php endif; ?>

document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => { try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch(e){} }, 5000);
});
</script>
</body>
</html>