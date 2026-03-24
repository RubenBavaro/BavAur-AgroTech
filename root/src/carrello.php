<?php
require_once 'config/db.php';
require_once 'config/session.php';

// Solo clienti loggati
if (!isset($_SESSION['user']) || $_SESSION['user']['ruolo'] !== 'cliente') {
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Devi fare il login come cliente per accedere al carrello.'];
    header('Location: login.php'); exit;
}

$userId   = $_SESSION['user']['idUtente'];
$userName = $_SESSION['user']['nome'];

// ── CHECKOUT (POST) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $cartJson = $_POST['cart_data'] ?? '[]';
    $cart     = json_decode($cartJson, true);
    $idSede   = (int)($_POST['idSede'] ?? 0);
    $note     = trim($_POST['note'] ?? '') ?: null;

    if (empty($cart) || !$idSede) {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Seleziona una sede e aggiungi prodotti al carrello.'];
        header('Location: carrello.php'); exit;
    }

    // Trova o crea il CLIENTE per questo utente (per associarlo alla VENDITA)
    $stmtCli = $pdo->prepare("SELECT idCliente FROM CLIENTE WHERE contatti LIKE ?");
    $stmtCli->execute(['%utente:'.$userId.'%']);
    $cliRow = $stmtCli->fetch();

    if (!$cliRow) {
        // Crea il cliente anagrafico collegato all'utente
        $pdo->prepare("INSERT INTO CLIENTE (nome, nickname, contatti) VALUES (?,?,?)")
            ->execute([$userName, null, 'utente:'.$userId]);
        $idCliente = $pdo->lastInsertId();
    } else {
        $idCliente = $cliRow['idCliente'];
    }

    $pdo->beginTransaction();
    try {
        $totaleCalc = 0;
        $righe = [];

        // Prezzi simulati FISSI — devono corrispondere esattamente al JS del carrello
        $PREZZO_LAVORATO = 8.50;
        $PREZZO_FRESCO   = 3.50;

        foreach ($cart as $item) {
            $pid  = (int)$item['id'];
            $qta  = max(1, (int)($item['qta'] ?? 1));
            $tipo = $item['tipo'] ?? 'fresco';

            $idConfezione = null;
            $prezzo = ($tipo === 'lavorato') ? $PREZZO_LAVORATO : $PREZZO_FRESCO;

            if ($tipo === 'lavorato') {
                // Cerca la confezione con giacenza sufficiente (FIFO per data)
                $confQ = $pdo->prepare("
                    SELECT c.idConfezione, c.giacenza
                    FROM CONFEZIONE c
                    JOIN PRODUZIONE pr ON c.idProduzione = pr.idProduzione
                    WHERE pr.idProdottoProdotto = ?
                      AND c.giacenza >= ?
                    ORDER BY c.dataConfezionamento ASC
                    LIMIT 1");
                $confQ->execute([$pid, $qta]);
                $confRow = $confQ->fetch();

                if (!$confRow) {
                    // Prodotto esaurito o quantità non disponibile
                    $pdo->rollBack();
                    $_SESSION['flash'] = ['type'=>'error',
                        'msg'=>"Prodotto non disponibile nelle quantità richieste. Controlla il carrello."];
                    header('Location: carrello.php'); exit;
                }

                $idConfezione = $confRow['idConfezione'];

                // Decrementa giacenza — mai sotto 0 grazie al check sopra
                $pdo->prepare("
                    UPDATE CONFEZIONE
                    SET giacenza = giacenza - ?
                    WHERE idConfezione = ?
                      AND giacenza >= ?")
                    ->execute([$qta, $idConfezione, $qta]);
            }

            $totaleCalc += $prezzo * $qta;
            $righe[] = ['pid'=>$pid,'qta'=>$qta,'prezzo'=>$prezzo,'idConf'=>$idConfezione];
        }

        // Crea VENDITA
        $pdo->prepare("
            INSERT INTO VENDITA (dataVendita,totaleCalcolato,totalePagato,note,idCliente,idSede)
            VALUES (CURDATE(),?,?,?,?,?)")
            ->execute([$totaleCalc, $totaleCalc, $note, $idCliente, $idSede]);
        $idVendita = $pdo->lastInsertId();

        // Crea DETTAGLIO_VENDITA
        foreach ($righe as $r) {
            $pdo->prepare("
                INSERT INTO DETTAGLIO_VENDITA (quantita,prezzoUnitario,omaggio,idVendita,idProdotto,idConfezione)
                VALUES (?,?,0,?,?,?)")
                ->execute([$r['qta'],$r['prezzo'],$idVendita,$r['pid'],$r['idConf']]);
        }

        $pdo->commit();

        $_SESSION['last_order'] = [
            'idVendita'   => $idVendita,
            'totale'      => $totaleCalc,
            'nProdotti'   => count($righe),
        ];
        $_SESSION['flash'] = ['type'=>'success','msg'=>"✅ Ordine #$idVendita confermato! Totale: €".number_format($totaleCalc,2,',','.')];
        header('Location: ordini.php'); exit;

    } catch (Throwable $e) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Errore durante il checkout: '.$e->getMessage()];
        header('Location: carrello.php'); exit;
    }
}

// Sedi per il form
$sedi = $pdo->query("SELECT * FROM SEDE ORDER BY nomeSede")->fetchAll();

$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }
?>
<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Carrello — AgroManager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <script>(function(){ var t=localStorage.getItem('ag_theme')||'light'; document.documentElement.setAttribute('data-theme',t); })();</script>
</head>
<body>

<!-- Navbar -->
<nav class="pub-nav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="homepage.php" class="pub-nav-brand text-decoration-none"><span>🌿</span> AgroManager</a>
    <div class="pub-nav-actions">
      <button class="theme-toggle" id="themeToggle"><i class="fa-solid fa-moon" id="themeIcon"></i></button>
      <a href="ordini.php" class="btn-ag-outline btn-ag-sm" style="color:#fff;border-color:rgba(255,255,255,.4)">
        <i class="fa-solid fa-receipt"></i> I Miei Ordini
      </a>
      <a href="logout.php" class="btn-ag-outline btn-ag-sm" style="color:#fff;border-color:rgba(255,255,255,.4)">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
      </a>
    </div>
  </div>
</nav>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show m-0" style="border-radius:0;border-left:none;border-right:none">
  <div class="container"><?= h($flash['msg']) ?> <button class="btn-close" data-bs-dismiss="alert"></button></div>
</div>
<?php endif; ?>

<div class="container" style="padding:32px 16px;max-width:960px">

  <!-- Breadcrumb -->
  <nav class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="homepage.php">Home</a></li>
      <li class="breadcrumb-item active">Carrello</li>
    </ol>
  </nav>

  <div class="row g-4">

    <!-- ── LISTA PRODOTTI NEL CARRELLO ── -->
    <div class="col-lg-7">
      <div class="ag-card">
        <div class="ag-card-header">
          <h6 class="ag-card-title"><i class="fa-solid fa-basket-shopping"></i> Il tuo Carrello</h6>
          <button class="btn-danger-sm" id="clearCart"><i class="fa-solid fa-trash"></i> Svuota</button>
        </div>
        <div id="cartContent">
          <!-- Popolato da JS -->
        </div>
        <!-- Empty state -->
        <div id="emptyCart" class="empty-state" style="display:none">
          <i class="fa-solid fa-basket-shopping"></i>
          <p>Il carrello è vuoto.<br><a href="homepage.php#prodotti">Torna ai prodotti</a></p>
        </div>
      </div>
    </div>

    <!-- ── RIEPILOGO E CHECKOUT ── -->
    <div class="col-lg-5">
      <div class="ag-card" style="position:sticky;top:20px">
        <div class="ag-card-header">
          <h6 class="ag-card-title"><i class="fa-solid fa-receipt"></i> Riepilogo Ordine</h6>
        </div>
        <div class="p-4">

          <!-- Totale -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span style="color:var(--ag-text-muted)">Subtotale</span>
            <span id="subtotal" class="fw-bold" style="font-size:1.1rem;color:var(--ag-text)">€ 0,00</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom:1px solid var(--ag-border)">
            <span style="color:var(--ag-text-muted)">Consegna</span>
            <span class="badge-ok">Gratuita</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="fw-bold" style="font-size:1.05rem">Totale</span>
            <span id="totalDisplay" class="fw-bold" style="font-size:1.4rem;color:var(--ag-primary)">€ 0,00</span>
          </div>

          <!-- Info simulazione -->
          <div class="mb-4 p-3" style="background:var(--ag-pale);border-radius:12px;border:1px solid var(--ag-border);font-size:.8rem;color:var(--ag-primary)">
            <i class="fa-solid fa-circle-info me-1"></i>
            <strong>Ordine Simulato</strong><br>
            Nessun pagamento reale. I prezzi sono calcolati automaticamente e le giacenze vengono aggiornate.
          </div>

          <!-- Form checkout -->
          <form method="POST" id="checkoutForm">
            <input type="hidden" name="checkout" value="1">
            <input type="hidden" name="cart_data" id="cartDataInput">

            <div class="mb-3">
              <label class="form-label">Sede di Ritiro <span class="text-danger">*</span></label>
              <select name="idSede" class="form-select" required>
                <option value="">Seleziona sede...</option>
                <?php foreach ($sedi as $s): ?>
                <option value="<?= $s['idSede'] ?>"><?= h($s['nomeSede']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label">Note (opzionale)</label>
              <textarea name="note" class="form-control" rows="2" placeholder="Preferenze, orari di ritiro..."></textarea>
            </div>

            <button type="submit" class="btn-ag w-100" style="justify-content:center;padding:14px;font-size:1rem" id="checkoutBtn">
              <i class="fa-solid fa-bag-shopping"></i> Conferma Ordine Simulato
            </button>
          </form>

          <div class="text-center mt-3">
            <a href="homepage.php#prodotti" style="font-size:.82rem;color:var(--ag-text-muted)">
              <i class="fa-solid fa-arrow-left me-1"></i>Continua lo shopping
            </a>
          </div>
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
applyTheme(localStorage.getItem('ag_theme')||'light');
document.getElementById('themeToggle').addEventListener('click', () => {
  applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark');
});

// Cart helpers
function getCart()     { return JSON.parse(localStorage.getItem('ag_cart') || '[]'); }
function saveCart(c)   { localStorage.setItem('ag_cart', JSON.stringify(c)); renderCart(); }

// Prezzi: legge il prezzo salvato nell'item (che viene dal server via data-prezzo)
// Fallback fisso in caso di item vecchi nel localStorage
function getPrezzoUnitario(item) {
  if (item.prezzo && item.prezzo > 0) return parseFloat(item.prezzo);
  return item.tipo === 'lavorato' ? 8.50 : 3.50;
}

function renderCart() {
  const cart    = getCart();
  const content = document.getElementById('cartContent');
  const empty   = document.getElementById('emptyCart');
  const btn     = document.getElementById('checkoutBtn');

  if (!cart.length) {
    content.innerHTML = '';
    empty.style.display = 'block';
    btn.disabled = true;
    btn.style.opacity = '.5';
    updateTotale([]);
    return;
  }
  empty.style.display = 'none';
  btn.disabled = false;
  btn.style.opacity = '1';

  let html = '<div style="padding:8px 0">';
  cart.forEach((item, idx) => {
    const prezzo = getPrezzoUnitario(item);
    const sub    = (prezzo * item.qta).toFixed(2).replace('.',',');
    html += `
    <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--ag-border)">
      <div style="flex:1;min-width:0">
        <div style="font-weight:600;font-size:.9rem;color:var(--ag-text)">${item.nome}</div>
        <div style="font-size:.78rem;color:var(--ag-text-muted);margin-top:2px">
          <span class="badge-${item.tipo}" style="font-size:.7rem">${item.tipo}</span>
          &nbsp;·&nbsp; €${prezzo.toFixed(2).replace('.',',')} / ${item.unita}
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <button onclick="changeQty(${idx},-1)" style="width:28px;height:28px;border-radius:8px;border:1.5px solid var(--ag-border);background:var(--ag-surface2);color:var(--ag-text);cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center">−</button>
        <span style="min-width:22px;text-align:center;font-weight:600;color:var(--ag-text)">${item.qta}</span>
        <button onclick="changeQty(${idx},+1)" style="width:28px;height:28px;border-radius:8px;border:1.5px solid var(--ag-border);background:var(--ag-surface2);color:var(--ag-text);cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center">+</button>
      </div>
      <div style="font-weight:700;color:var(--ag-primary);min-width:56px;text-align:right">€${sub}</div>
      <button onclick="removeItem(${idx})" style="background:#fee2e2;border:none;border-radius:8px;color:#dc2626;padding:5px 8px;cursor:pointer;flex-shrink:0">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>`;
  });
  html += '</div>';
  content.innerHTML = html;
  updateTotale(cart);
}

function updateTotale(cart) {
  const tot = cart.reduce((s,i) => s + getPrezzoUnitario(i)*i.qta, 0);
  const fmt = tot.toFixed(2).replace('.',',');
  document.getElementById('subtotal').textContent     = '€ '+fmt;
  document.getElementById('totalDisplay').textContent = '€ '+fmt;
}

function changeQty(idx, delta) {
  let cart = getCart();
  cart[idx].qta = Math.max(1, cart[idx].qta + delta);
  saveCart(cart);
}

function removeItem(idx) {
  let cart = getCart();
  cart.splice(idx, 1);
  saveCart(cart);
}

document.getElementById('clearCart').addEventListener('click', () => {
  if (confirm('Svuotare il carrello?')) { localStorage.removeItem('ag_cart'); renderCart(); }
});

// Prepara i dati prima dell'invio
document.getElementById('checkoutForm').addEventListener('submit', (e) => {
  const cart = getCart();
  if (!cart.length) { e.preventDefault(); alert('Il carrello è vuoto!'); return; }
  document.getElementById('cartDataInput').value = JSON.stringify(cart);
  // Svuota il carrello lato client dopo submit riuscito
  // (la pagina redirige, quindi lo svuotiamo nella pagina ordini)
});

// Init
renderCart();
</script>
</body>
</html>