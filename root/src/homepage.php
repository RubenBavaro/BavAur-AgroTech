<?php
require_once 'config/db.php';

// Leggi sessione solo se esiste già (non crearla)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLogged = isset($_SESSION['user']);
$userRuolo = $_SESSION['user']['ruolo'] ?? '';
$userName  = $_SESSION['user']['nome']  ?? '';
$isAdmin   = in_array($userRuolo, ['superadmin','sede_admin']);
$isCliente = $userRuolo === 'cliente';

// Flash message dalla sessione
$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

// Prodotti con categoria
$prodotti = $pdo->query("
    SELECT p.*, c.nomeCategoria
    FROM PRODOTTO p
    LEFT JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
    ORDER BY p.tipoProdotto DESC, p.nome
")->fetchAll();

// Giacenza totale disponibile per ogni prodotto lavorato
$giacenzeRaw = $pdo->query("
    SELECT pr.idProdottoProdotto AS idProdotto, COALESCE(SUM(c.giacenza),0) AS totGiac
    FROM CONFEZIONE c
    JOIN PRODUZIONE pr ON c.idProduzione = pr.idProduzione
    GROUP BY pr.idProdottoProdotto
")->fetchAll();
$giacenze = array_column($giacenzeRaw, 'totGiac', 'idProdotto'); // [idProdotto => totGiac]

// Prezzi simulati (stessi del JS del carrello e del PHP checkout)
define('PREZZO_LAVORATO', 8.50);
define('PREZZO_FRESCO',   3.50);

// Statistiche vitrina
$stats = [
    'prodotti' => $pdo->query("SELECT COUNT(*) FROM PRODOTTO")->fetchColumn(),
    'sedi'     => $pdo->query("SELECT COUNT(*) FROM SEDE")->fetchColumn() - 1,
    'clienti' => $pdo->query("SELECT COUNT(*) FROM CLIENTE")->fetchColumn() - 5,];
?>
<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AgroManager — BavAur-AgroTech</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <script>(function(){ var t=localStorage.getItem('ag_theme')||'light'; document.documentElement.setAttribute('data-theme',t); })();</script>
</head>
<body>

<!-- ══ NAVBAR PUBBLICA ════════════════════════════════════════ -->
<nav class="pub-nav">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <!-- Brand -->
      <a href="homepage.php" class="pub-nav-brand text-decoration-none">
        <span>🌿</span> AgroManager
      </a>

      <!-- Links centro -->
      <div class="d-none d-md-flex align-items-center gap-4" style="font-size:.88rem">
        <a href="#prodotti" style="color:rgba(255,255,255,.75);transition:color .2s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.75)'">
          <i class="fa-solid fa-seedling me-1"></i>Prodotti
        </a>
        <a href="#chi-siamo" style="color:rgba(255,255,255,.75);transition:color .2s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.75)'">
          <i class="fa-solid fa-leaf me-1"></i>Chi Siamo
        </a>
        <a href="#sedi" style="color:rgba(255,255,255,.75);transition:color .2s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.75)'">
          <i class="fa-solid fa-location-dot me-1"></i>Le Nostre Sedi
        </a>
      </div>

      <!-- Azioni destra -->
      <div class="pub-nav-actions">
        <!-- Theme toggle -->
        <button class="theme-toggle" id="themeToggle" title="Cambia tema">
          <i class="fa-solid fa-moon" id="themeIcon"></i>
        </button>

        <?php if ($isAdmin): ?>
          <!-- Admin → vai alla dashboard -->
          <a href="dashboard.php" class="btn-ag btn-ag-sm">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
          </a>
        <?php elseif ($isCliente): ?>
          <!-- Cliente loggato -->
          <a href="carrello.php" class="btn-ag-outline btn-ag-sm" style="color:#fff;border-color:rgba(255,255,255,.4);position:relative" id="cartBtn">
            <i class="fa-solid fa-basket-shopping"></i> Carrello
            <span id="cartCount" class="cart-badge" style="display:none">0</span>
          </a>
          <div class="dropdown">
            <button class="btn-ag btn-ag-sm dropdown-toggle" data-bs-toggle="dropdown" style="gap:6px">
              <i class="fa-solid fa-circle-user"></i> <?= h(explode(' ',$userName)[0]) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="border:1px solid var(--ag-border);border-radius:12px;padding:6px;box-shadow:var(--ag-shadow-lg)">
              <li><a class="dropdown-item" href="ordini.php" style="border-radius:8px;font-size:.875rem">
                <i class="fa-solid fa-receipt me-2" style="color:var(--ag-primary)"></i>I Miei Ordini
              </a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php" style="border-radius:8px;font-size:.875rem">
                <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
              </a></li>
            </ul>
          </div>
        <?php else: ?>
          <!-- Non loggato -->
          <a href="login.php" class="btn-ag-outline btn-ag-sm" style="color:#fff;border-color:rgba(255,255,255,.4)">
            <i class="fa-solid fa-right-to-bracket"></i> Accedi
          </a>
          <a href="register.php" class="btn-ag btn-ag-sm">
            <i class="fa-solid fa-user-plus"></i> Registrati
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- Flash message -->
<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show m-0" role="alert" style="border-radius:0;border-left:none;border-right:none">
  <div class="container d-flex align-items-center gap-2">
    <i class="fa-solid fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= h($flash['msg']) ?>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>

<!-- ══ HERO ══════════════════════════════════════════════════ -->
<section class="hero">
  <div class="container hero-content">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <div class="hero-badge">
          <span>🌱</span> Prodotti freschi dalla campagna pugliese
        </div>
        <h1>La natura in ogni<br>prodotto</h1>
        <p>Dalla produzione alla tua tavola. Miele artigianale, oli extravergine, conserve e prodotti freschi coltivati con cura nel cuore della Puglia.</p>
        <div class="d-flex flex-wrap gap-3">
          <a href="#prodotti" class="btn-ag" style="font-size:1rem;padding:13px 28px">
            <i class="fa-solid fa-basket-shopping"></i> Scopri i Prodotti
          </a>
          <?php if (!$isLogged): ?>
          <a href="register.php" class="btn-ag-outline" style="font-size:1rem;padding:12px 28px;color:#fff;border-color:rgba(255,255,255,.5)">
            <i class="fa-solid fa-user-plus"></i> Registrati Gratis
          </a>
          <?php elseif ($isCliente): ?>
          <a href="carrello.php" class="btn-ag-outline" style="font-size:1rem;padding:12px 28px;color:#fff;border-color:rgba(255,255,255,.5)">
            <i class="fa-solid fa-basket-shopping"></i> Vai al Carrello
          </a>
          <?php endif; ?>
        </div>
      </div>
      <!-- Stats -->
      <div class="col-lg-5 d-none d-lg-block">
        <div class="d-grid" style="grid-template-columns:1fr 1fr;gap:14px">
          <?php foreach ([
            ['value'=>$stats['prodotti'],'label'=>'Prodotti','icon'=>'fa-seedling'],
            ['value'=>$stats['sedi'],    'label'=>'Sedi Operative','icon'=>'fa-location-dot'],
            ['value'=>$stats['clienti'], 'label'=>'Clienti Attivi','icon'=>'fa-users'],
            ['value'=>'100%',            'label'=>'Naturale','icon'=>'fa-leaf'],
          ] as $hs): ?>
          <div style="background:rgba(255,255,255,.12);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.2);border-radius:16px;padding:20px;text-align:center">
            <i class="fa-solid <?= $hs['icon'] ?>" style="font-size:1.5rem;color:var(--ag-light);margin-bottom:8px;display:block"></i>
            <div style="font-size:1.8rem;font-weight:800;color:#fff"><?= $hs['value'] ?></div>
            <div style="font-size:.8rem;color:rgba(255,255,255,.7)"><?= $hs['label'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ BANNER CLIENTE LOGGATO ════════════════════════════════ -->
<?php if ($isCliente): ?>
<div style="background:linear-gradient(90deg,var(--ag-primary),var(--ag-medium));color:#fff;padding:14px 0">
  <div class="container d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div style="font-size:.95rem">
      <i class="fa-solid fa-circle-check me-2" style="color:var(--ag-pale)"></i>
      Benvenuto, <strong><?= h($userName) ?></strong>! Aggiungi prodotti al carrello e simula il tuo ordine.
    </div>
    <a href="carrello.php" class="btn-ag" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);padding:8px 18px">
      <i class="fa-solid fa-basket-shopping"></i> Vai al Carrello
    </a>
  </div>
</div>
<?php endif; ?>

<!-- ══ FEATURES ══════════════════════════════════════════════ -->
<section class="pub-section" id="chi-siamo">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Perché sceglierci</div>
      <h2 class="section-title">La qualità che si vede</h2>
    </div>
    <div class="row g-4">
      <?php foreach ([
        ['fa-leaf',              '100% Naturale',        'Tutti i prodotti provengono da coltivazioni biologiche, senza pesticidi né additivi chimici.'],
        ['fa-hand-holding-heart','Produzione Artigianale','Ogni prodotto è lavorato a mano seguendo ricette tradizionali tramandate di generazione in generazione.'],
        ['fa-map-location-dot', 'Km Zero',               'Produciamo e vendiamo in Puglia. La filiera cortissima garantisce freschezza e supporta il territorio.'],
        ['fa-shield-halved',    'Tracciabilità Totale',  'Ogni prodotto è tracciato dalla produzione alla vendita. Sai esattamente cosa stai comprando.'],
      ] as $f): ?>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid <?= $f[0] ?>"></i></div>
          <div class="feature-title"><?= $f[1] ?></div>
          <div class="feature-text"><?= $f[2] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ PRODOTTI ══════════════════════════════════════════════ -->
<section class="pub-section pub-section-alt" id="prodotti">
  <div class="container">
    <div class="d-flex align-items-end justify-content-between mb-5 flex-wrap gap-3">
      <div>
        <div class="section-label">Il nostro catalogo</div>
        <h2 class="section-title mb-0">I Nostri Prodotti</h2>
      </div>
      <div class="d-flex gap-2 flex-wrap" id="filterTabs">
        <button class="btn-ag" data-filter="all">Tutti</button>
        <button class="btn-ag-outline" data-filter="fresco">🥬 Freschi</button>
        <button class="btn-ag-outline" data-filter="lavorato">🍯 Lavorati</button>
      </div>
    </div>

    <div class="row g-4" id="productsGrid">
      <?php foreach ($prodotti as $p):
        $pid      = $p['idProdotto'];
        $tipo     = $p['tipoProdotto'];
        $prezzo   = $tipo === 'lavorato' ? PREZZO_LAVORATO : PREZZO_FRESCO;
        // Stock: i freschi non hanno confezioni → sempre disponibili
        $giac     = $tipo === 'lavorato' ? (int)($giacenze[$pid] ?? 0) : PHP_INT_MAX;
        $esaurito = ($tipo === 'lavorato' && $giac === 0);
      ?>
      <div class="col-sm-6 col-lg-4 col-xl-3 product-item" data-tipo="<?= h($tipo) ?>">
        <div class="product-card" style="<?= $esaurito ? 'opacity:.7' : '' ?>">

          <!-- Immagine con overlay esaurito -->
          <div class="product-card-img-wrap" style="position:relative">
            <img src="<?= getProductImage($p['nome'], $p['nomeCategoria'] ?? '', $tipo, $p['immagineUrl'] ?? null) ?>"
                 alt="<?= h($p['nome']) ?>" class="product-card-img" loading="lazy"
                 onerror="this.src='https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=500&h=360&fit=crop'">
            <?php if ($esaurito): ?>
            <div style="position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;border-radius:0">
              <span style="background:#dc2626;color:#fff;font-weight:700;font-size:.9rem;padding:6px 18px;border-radius:99px;letter-spacing:.5px">
                ESAURITO
              </span>
            </div>
            <?php elseif ($tipo === 'lavorato' && $giac <= 10): ?>
            <div style="position:absolute;top:10px;left:10px">
              <span style="background:#d97706;color:#fff;font-weight:600;font-size:.72rem;padding:4px 10px;border-radius:99px">
                Ultimi <?= $giac ?> rimasti
              </span>
            </div>
            <?php endif; ?>
          </div>

          <div class="product-card-body">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
              <div class="product-card-name"><?= h($p['nome']) ?></div>
              <span class="badge-<?= h($tipo) ?>" style="white-space:nowrap;flex-shrink:0">
                <?= ucfirst($tipo) ?>
              </span>
            </div>
            <?php if ($p['nomeCategoria']): ?>
            <div class="product-card-cat">
              <i class="fa-solid fa-tag me-1" style="font-size:.7rem"></i><?= h($p['nomeCategoria']) ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($p['descrizione'])): ?>
            <div class="product-card-desc"><?= h($p['descrizione']) ?></div>
            <?php endif; ?>

            <!-- Prezzo -->
            <div style="font-size:1.1rem;font-weight:800;color:var(--ag-primary);margin:10px 0 6px">
              €<?= number_format($prezzo, 2, ',', '.') ?>
              <span style="font-size:.75rem;font-weight:400;color:var(--ag-text-muted)">/ <?= h($p['unitaMisura']) ?></span>
            </div>

            <div class="d-flex align-items-center justify-content-between gap-2">
              <!-- Giacenza badge -->
              <?php if ($tipo === 'lavorato'): ?>
                <?php if ($esaurito): ?>
                  <span class="badge-zero"><i class="fa-solid fa-ban me-1"></i>Esaurito</span>
                <?php elseif ($giac <= 10): ?>
                  <span class="badge-low"><i class="fa-solid fa-triangle-exclamation me-1"></i><?= $giac ?> disp.</span>
                <?php else: ?>
                  <span class="badge-ok"><i class="fa-solid fa-check me-1"></i>Disponibile</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge-ok"><i class="fa-solid fa-check me-1"></i>Fresco</span>
              <?php endif; ?>

              <!-- Bottone azione -->
              <?php if ($isCliente): ?>
                <?php if ($esaurito): ?>
                  <button class="btn-ag btn-ag-sm" disabled style="opacity:.45;cursor:not-allowed;padding:6px 14px;font-size:.8rem">
                    <i class="fa-solid fa-ban"></i> Esaurito
                  </button>
                <?php else: ?>
                  <button class="btn-ag btn-ag-sm add-to-cart"
                          data-id="<?= $pid ?>"
                          data-nome="<?= h($p['nome']) ?>"
                          data-tipo="<?= h($tipo) ?>"
                          data-unita="<?= h($p['unitaMisura']) ?>"
                          data-prezzo="<?= $prezzo ?>"
                          style="padding:6px 14px;font-size:.8rem">
                    <i class="fa-solid fa-plus"></i> Aggiungi
                  </button>
                <?php endif; ?>
              <?php elseif (!$isLogged): ?>
                <a href="login.php" style="font-size:.78rem;color:var(--ag-primary);white-space:nowrap">
                  <i class="fa-solid fa-lock me-1"></i>Accedi
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($prodotti)): ?>
    <div class="empty-state"><i class="fa-solid fa-seedling"></i><p>Nessun prodotto disponibile.</p></div>
    <?php endif; ?>
  </div>
</section>
<?php
$sediPub = $pdo->query("SELECT idSede, nomeSede, indirizzo FROM SEDE ORDER BY nomeSede")->fetchAll();
?>
<section class="pub-section" id="sedi">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Dove trovarci</div>
      <h2 class="section-title">Le Nostre Sedi</h2>
      <p class="section-sub mx-auto">Vieni a trovarci direttamente nelle nostre sedi operative per acquistare i prodotti freschi.</p>
    </div>
    <div class="row g-4 justify-content-center">
      <?php foreach ($sediPub as $s): ?>
      <div class="col-md-6 col-lg-4">
        <div class="feature-card" style="text-align:left;height:100%">
          <div class="feature-icon" style="margin:0 0 14px"><i class="fa-solid fa-location-dot"></i></div>
          <div class="feature-title"><?= h($s['nomeSede']) ?></div>
          <div class="feature-text">
            <i class="fa-solid fa-map-pin me-1" style="color:var(--ag-primary)"></i>
            <?= h($s['indirizzo'] ?? 'Indirizzo non disponibile') ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ CTA ═══════════════════════════════════════════════════ -->
<?php if (!$isLogged): ?>
<section class="pub-section" style="background:linear-gradient(135deg,#1B4332,#2D6A4F)">
  <div class="container text-center" style="color:#fff">
    <h2 style="font-size:2rem;font-weight:800;margin-bottom:16px">Inizia oggi</h2>
    <p style="font-size:1.05rem;opacity:.8;max-width:480px;margin:0 auto 32px">
      Crea il tuo account gratuitamente e inizia ad acquistare direttamente dall'azienda.
    </p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="register.php" class="btn-ag" style="background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.4);font-size:1rem;padding:13px 28px">
        <i class="fa-solid fa-user-plus"></i> Registrati Gratis
      </a>
      <a href="login.php" class="btn-ag-outline" style="color:#fff;border-color:rgba(255,255,255,.4);font-size:1rem;padding:13px 28px">
        <i class="fa-solid fa-right-to-bracket"></i> Accedi
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ FOOTER ════════════════════════════════════════════════ -->
<footer class="pub-footer">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 mb-3 mb-md-0">
        <div style="font-size:1.1rem;font-weight:700;color:rgba(255,255,255,.8);margin-bottom:6px">🌿 AgroManager</div>
        <div>Sistema Informativo per la Gestione Digitale di una Azienda Agricola</div>
      </div>
      <div class="col-md-6 text-md-end">
        <div>Sviluppato da <strong>Ruben Bavaro</strong> & <strong>Raffaele Auriole</strong></div>
        <div class="mt-1">
          <a href="https://github.com/RubenBavaro" target="_blank" style="color:rgba(255,255,255,.5);margin-right:12px">
            <i class="fa-brands fa-github"></i> RubenBavaro
          </a>
          <a href="https://github.com/RaffaeleeAuriole" target="_blank" style="color:rgba(255,255,255,.5)">
            <i class="fa-brands fa-github"></i> RaffaeleeAuriole
          </a>
        </div>
      </div>
    </div>
  </div>
</footer>

<!-- ══ TOAST CARRELLO ════════════════════════════════════════ -->
<div id="cartToast" style="
  position:fixed;bottom:24px;right:24px;z-index:9999;
  background:var(--ag-primary);color:#fff;
  border-radius:14px;padding:14px 20px;
  box-shadow:var(--ag-shadow-lg);
  display:none;align-items:center;gap:10px;
  font-size:.88rem;font-weight:600;
  max-width:300px;
">
  <i class="fa-solid fa-circle-check" style="font-size:1.1rem"></i>
  <span id="cartToastMsg">Prodotto aggiunto!</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Theme ────────────────────────────────────────────────────
const html = document.documentElement;
function applyTheme(t) {
  html.setAttribute('data-theme', t);
  document.getElementById('themeIcon').className = t==='dark'?'fa-solid fa-sun':'fa-solid fa-moon';
  localStorage.setItem('ag_theme', t);
}
applyTheme(localStorage.getItem('ag_theme') || 'light');
document.getElementById('themeToggle').addEventListener('click', () => {
  applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
});

// ── Filter prodotti ───────────────────────────────────────────
document.querySelectorAll('#filterTabs button').forEach(btn => {
  btn.addEventListener('click', () => {
    const f = btn.dataset.filter;
    document.querySelectorAll('#filterTabs button').forEach(b => {
      b.className = b === btn ? 'btn-ag' : 'btn-ag-outline';
    });
    document.querySelectorAll('.product-item').forEach(item => {
      item.style.display = (f === 'all' || item.dataset.tipo === f) ? '' : 'none';
    });
  });
});

// ── Carrello localStorage ─────────────────────────────────────
<?php if ($isCliente): ?>
function getCart() { return JSON.parse(localStorage.getItem('ag_cart') || '[]'); }
function saveCart(c) { localStorage.setItem('ag_cart', JSON.stringify(c)); updateCartBadge(); }
function updateCartBadge() {
  const cart = getCart();
  const total = cart.reduce((s, i) => s + i.qta, 0);
  const badge = document.getElementById('cartCount');
  if (badge) {
    badge.textContent = total;
    badge.style.display = total > 0 ? 'flex' : 'none';
  }
}

document.querySelectorAll('.add-to-cart').forEach(btn => {
  btn.addEventListener('click', () => {
    const id     = parseInt(btn.dataset.id);
    const nome   = btn.dataset.nome;
    const tipo   = btn.dataset.tipo;
    const unita  = btn.dataset.unita;
    const prezzo = parseFloat(btn.dataset.prezzo);   // prezzo dal server, sempre corretto
    let cart     = getCart();
    const idx    = cart.findIndex(i => i.id === id);
    if (idx >= 0) { cart[idx].qta += 1; }
    else { cart.push({ id, nome, tipo, unita, prezzo, qta: 1 }); }
    saveCart(cart);

    // Feedback visivo
    const toast = document.getElementById('cartToast');
    document.getElementById('cartToastMsg').textContent = `«${nome}» aggiunto al carrello!`;
    toast.style.display = 'flex';
    clearTimeout(toast._t);
    toast._t = setTimeout(() => toast.style.display = 'none', 2800);

    // Animazione bottone
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Aggiunto';
    btn.style.background = '#16a34a';
    setTimeout(() => {
      btn.innerHTML = '<i class="fa-solid fa-plus"></i> Aggiungi';
      btn.style.background = '';
    }, 1500);
  });
});

updateCartBadge();
<?php endif; ?>

// Auto-dismiss flash
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => { try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch(e){} }, 4000);
});
</script>

<style>
.cart-badge {
  position:absolute;top:-6px;right:-6px;
  background:#dc2626;color:#fff;
  border-radius:99px;width:18px;height:18px;
  font-size:.65rem;font-weight:700;
  display:flex;align-items:center;justify-content:center;
  border:2px solid rgba(27,67,50,.95);
}
</style>
</body>
</html>
