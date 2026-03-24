<?php
require_once 'includes/auth.php';
$currentPage = 'dashboard';
$pageTitle   = 'Dashboard';
$basePath    = '';
$mySedeId    = userSede();

// ── Statistiche (filtrate per sede se sede_admin) ─────────────
$sw = $mySedeId ? "WHERE idSede=$mySedeId" : '';
$stats = [
    'CLIENTE'    => $pdo->query("SELECT COUNT(*) FROM CLIENTE")->fetchColumn(),
    'PRODOTTO'   => $pdo->query("SELECT COUNT(*) FROM PRODOTTO")->fetchColumn(),
    'CATEGORIA'  => $pdo->query("SELECT COUNT(*) FROM CATEGORIA")->fetchColumn(),
    'VENDITA'    => $pdo->query("SELECT COUNT(*) FROM VENDITA $sw")->fetchColumn(),
    'PRODUZIONE' => $pdo->query("SELECT COUNT(*) FROM PRODUZIONE $sw")->fetchColumn(),
    'CONFEZIONE' => $pdo->query("SELECT COUNT(*) FROM CONFEZIONE" .
        ($mySedeId ? " WHERE idProduzione IN (SELECT idProduzione FROM PRODUZIONE WHERE idSede=$mySedeId)" : ''))
        ->fetchColumn(),
];

// Incasso mese corrente
$totMeseQ = "SELECT COALESCE(SUM(totalePagato),0) FROM VENDITA WHERE MONTH(dataVendita)=MONTH(CURDATE()) AND YEAR(dataVendita)=YEAR(CURDATE())" . ($mySedeId ? " AND idSede=$mySedeId" : '');
$totMese  = $pdo->query($totMeseQ)->fetchColumn();

// Giacenze basse (< 20%)
$confLowQ = "SELECT c.idConfezione, c.giacenza, c.numeroConfezioni, pp.nome AS prodotto, pp.unitaMisura
    FROM CONFEZIONE c
    JOIN PRODUZIONE pr ON c.idProduzione=pr.idProduzione
    JOIN PRODOTTO   pp ON pr.idProdottoProdotto=pp.idProdotto
    WHERE c.giacenza < (c.numeroConfezioni * 0.2)" .
    ($mySedeId ? " AND pr.idSede=$mySedeId" : '') . " ORDER BY c.giacenza ASC LIMIT 6";
$confLow = $pdo->query($confLowQ)->fetchAll();

// Ultime 5 vendite
$ultVendQ = "SELECT v.idVendita,v.dataVendita,v.totalePagato,cl.nome AS cliente,s.nomeSede,COUNT(d.idDettaglio) AS nRighe
    FROM VENDITA v JOIN CLIENTE cl ON v.idCliente=cl.idCliente JOIN SEDE s ON v.idSede=s.idSede
    LEFT JOIN DETTAGLIO_VENDITA d ON d.idVendita=v.idVendita
    " . ($mySedeId ? "WHERE v.idSede=$mySedeId" : '') . "
    GROUP BY v.idVendita ORDER BY v.dataVendita DESC, v.idVendita DESC LIMIT 5";
$ultVend = $pdo->query($ultVendQ)->fetchAll();

// Produzioni recenti
$ultProdQ = "SELECT pr.idProduzione,pr.dataProduzione,pr.quantitaProdotta,pp.nome AS prodotto,pp.unitaMisura,s.nomeSede
    FROM PRODUZIONE pr JOIN PRODOTTO pp ON pr.idProdottoProdotto=pp.idProdotto JOIN SEDE s ON pr.idSede=s.idSede
    " . ($mySedeId ? "WHERE pr.idSede=$mySedeId" : '') . "
    ORDER BY pr.dataProduzione DESC LIMIT 5";
$ultProd = $pdo->query($ultProdQ)->fetchAll();

require_once 'includes/header.php';
?>

<!-- ── STAT CARDS ──────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['icon'=>'fa-users',        'color'=>'green',  'value'=>$stats['CLIENTE'],    'label'=>'Clienti',          'link'=>'clienti.php'],
    ['icon'=>'fa-receipt',      'color'=>'blue',   'value'=>$stats['VENDITA'],    'label'=>'Vendite',           'link'=>'vendite.php'],
    ['icon'=>'fa-seedling',     'color'=>'teal',   'value'=>$stats['PRODOTTO'],   'label'=>'Prodotti',          'link'=>'prodotti.php'],
    ['icon'=>'fa-gears',        'color'=>'orange', 'value'=>$stats['PRODUZIONE'], 'label'=>'Produzioni',        'link'=>'produzioni.php'],
    ['icon'=>'fa-box-open',     'color'=>'green',  'value'=>$stats['CONFEZIONE'], 'label'=>'Confezioni',        'link'=>'confezioni.php'],
    ['icon'=>'fa-euro-sign',    'color'=>'teal',   'value'=>'€'.number_format($totMese,0,',','.'), 'label'=>'Incasso Mese', 'link'=>'vendite.php'],
  ];
  foreach ($cards as $card): ?>
  <div class="col-6 col-xl-2 col-md-4">
    <a href="<?= $card['link'] ?>" class="text-decoration-none">
      <div class="stat-card">
        <div class="stat-icon <?= $card['color'] ?>"><i class="fa-solid <?= $card['icon'] ?>"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $card['value'] ?></div>
          <div class="stat-label"><?= $card['label'] ?></div>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── AZIONI RAPIDE ───────────────────────────────────────── -->
<div class="ag-card mb-4">
  <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-bolt"></i> Azioni Rapide</h6></div>
  <div class="p-3 d-flex flex-wrap gap-2">
    <a href="vendite.php?action=create"    class="btn-ag"><i class="fa-solid fa-plus"></i> Nuova Vendita</a>
    <a href="clienti.php?action=create"    class="btn-ag-outline"><i class="fa-solid fa-user-plus"></i> Nuovo Cliente</a>
    <a href="produzioni.php?action=create" class="btn-ag-outline"><i class="fa-solid fa-flask"></i> Nuova Produzione</a>
    <a href="confezioni.php?action=create" class="btn-ag-outline"><i class="fa-solid fa-box"></i> Nuova Confezione</a>
    <a href="prodotti.php?action=create"   class="btn-ag-outline"><i class="fa-solid fa-seedling"></i> Nuovo Prodotto</a>
    <a href="sedi.php"                     class="btn-ag-outline"><i class="fa-solid fa-location-dot"></i> Gestisci Sedi</a>
  </div>
</div>

<!-- ── CONTENUTO PRINCIPALE ────────────────────────────────── -->
<div class="row g-4">

  <!-- Ultime vendite -->
  <div class="col-lg-7">
    <div class="ag-card">
      <div class="ag-card-header">
        <h6 class="ag-card-title"><i class="fa-solid fa-receipt"></i> Ultime Vendite</h6>
        <a href="vendite.php" class="btn-ag btn-ag-sm">Vedi tutte</a>
      </div>
      <div class="table-responsive">
        <?php if(empty($ultVend)): ?>
          <div class="empty-state" style="padding:30px"><i class="fa-solid fa-receipt"></i><p>Nessuna vendita.</p></div>
        <?php else: ?>
        <table class="ag-table table mb-0">
          <thead><tr><th>#</th><th>Data</th><th>Cliente</th><th>Sede</th><th>Pagato</th><th></th></tr></thead>
          <tbody>
          <?php foreach($ultVend as $v): ?>
          <tr>
            <td class="text-muted fw-semibold">#<?= $v['idVendita'] ?></td>
            <td><?= date('d/m/Y',strtotime($v['dataVendita'])) ?></td>
            <td class="fw-semibold"><?= h($v['cliente']) ?></td>
            <td><small class="text-muted"><?= h($v['nomeSede']) ?></small></td>
            <td class="fw-bold" style="color:var(--ag-primary)">€<?= number_format($v['totalePagato'],2,',','.') ?></td>
            <td><a href="vendite.php?action=view&id=<?= $v['idVendita'] ?>" class="btn-view-sm"><i class="fa-solid fa-eye"></i></a></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Sidebar destra -->
  <div class="col-lg-5 d-flex flex-column gap-4">

    <!-- Giacenze basse -->
    <div class="ag-card">
      <div class="ag-card-header">
        <h6 class="ag-card-title"><i class="fa-solid fa-triangle-exclamation" style="color:#d97706"></i> Giacenze Basse</h6>
        <a href="confezioni.php" class="btn-ag btn-ag-sm">Gestisci</a>
      </div>
      <div class="p-3">
        <?php if(empty($confLow)): ?>
          <div class="empty-state" style="padding:20px"><i class="fa-solid fa-check-circle" style="color:var(--ag-primary)"></i><p>Tutte le giacenze sono regolari.</p></div>
        <?php else: ?>
          <?php foreach($confLow as $ls):
            $pct   = $ls['numeroConfezioni']>0 ? ($ls['giacenza']/$ls['numeroConfezioni']*100) : 0;
            $color = $ls['giacenza']==0 ? '#dc2626' : ($pct<10 ? '#d97706' : '#2D6A4F');
            $bc    = $ls['giacenza']==0 ? 'badge-zero' : 'badge-low';
          ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span class="fw-semibold" style="font-size:.85rem"><?= h($ls['prodotto']) ?></span>
              <span class="<?= $bc ?>"><?= $ls['giacenza']==0?'Esaurito':$ls['giacenza'].'/'.$ls['numeroConfezioni'] ?></span>
            </div>
            <div class="giacenza-bar">
              <div class="giacenza-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Produzioni recenti -->
    <div class="ag-card">
      <div class="ag-card-header">
        <h6 class="ag-card-title"><i class="fa-solid fa-gears"></i> Produzioni Recenti</h6>
        <a href="produzioni.php" class="btn-ag btn-ag-sm">Vedi tutte</a>
      </div>
      <div class="px-3 py-2">
        <?php if(empty($ultProd)): ?>
          <div class="empty-state" style="padding:20px"><i class="fa-solid fa-gears"></i><p>Nessuna produzione.</p></div>
        <?php else: ?>
          <?php foreach($ultProd as $p): ?>
          <div class="activity-item">
            <div class="activity-dot"></div>
            <div>
              <div class="activity-text"><strong><?= h($p['prodotto']) ?></strong> — <?= number_format($p['quantitaProdotta'],1) ?> <?= h($p['unitaMisura']) ?></div>
              <div class="activity-date"><?= date('d/m/Y',strtotime($p['dataProduzione'])) ?> · <?= h($p['nomeSede']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- ── NAVIGAZIONE SEZIONI ──────────────────────────────────── -->
<div class="row g-3 mt-2">
  <?php
  $sections = [
    ['icon'=>'fa-users',        'title'=>'Clienti',     'desc'=>'Gestisci l\'anagrafica clienti',          'href'=>'clienti.php',    'color'=>'green'],
    ['icon'=>'fa-tags',         'title'=>'Categorie',   'desc'=>'Categorie merceologiche prodotti',        'href'=>'categorie.php',  'color'=>'teal'],
    ['icon'=>'fa-seedling',     'title'=>'Prodotti',    'desc'=>'Catalogo completo dei prodotti',          'href'=>'prodotti.php',   'color'=>'green'],
    ['icon'=>'fa-gears',        'title'=>'Produzioni',  'desc'=>'Cicli produttivi registrati',             'href'=>'produzioni.php', 'color'=>'orange'],
    ['icon'=>'fa-box-open',     'title'=>'Confezioni',  'desc'=>'Lotti confezionati e giacenze',           'href'=>'confezioni.php', 'color'=>'teal'],
    ['icon'=>'fa-receipt',      'title'=>'Vendite',     'desc'=>'Registro vendite e dettagli',             'href'=>'vendite.php',    'color'=>'blue'],
    ['icon'=>'fa-location-dot', 'title'=>'Sedi',        'desc'=>'Sedi operative e giacenze',               'href'=>'sedi.php',       'color'=>'green'],
  ];
  foreach ($sections as $s): ?>
  <div class="col-md-4 col-xl-3">
    <a href="<?= $s['href'] ?>" class="text-decoration-none">
      <div class="feature-card" style="text-align:left;padding:18px 20px;transition:transform .2s,box-shadow .2s" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='var(--ag-shadow-lg)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div class="d-flex align-items-center gap-12px">
          <div class="stat-icon <?= $s['color'] ?>" style="width:40px;height:40px;border-radius:10px;font-size:1.1rem;flex-shrink:0">
            <i class="fa-solid <?= $s['icon'] ?>"></i>
          </div>
          <div>
            <div class="feature-title" style="margin-bottom:2px"><?= $s['title'] ?></div>
            <div class="feature-text" style="font-size:.78rem"><?= $s['desc'] ?></div>
          </div>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
