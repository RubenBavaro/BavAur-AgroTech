<?php
require_once 'includes/auth.php';
$currentPage = 'sedi';
$basePath    = '';
$action  = $_GET['action'] ?? 'list';
$id      = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isSuperA = isSuperAdmin();
$mySedeId = userSede();

// Sede admin: solo la propria sede, niente create/delete
if (!$isSuperA && in_array($action, ['create','delete'])) {
    flash('error','Non hai i permessi per questa operazione.'); redirect('sedi.php');
}

// ── AGGIORNA GIACENZA (POST da pagina manage) ─────────────────
if ($action === 'giacenza' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuovaGiac = (int)($_POST['giacenza'] ?? -1);
    $sedeBack  = (int)($_POST['idSede'] ?? 0);
    $chk = $pdo->prepare("
        SELECT c.idConfezione, c.numeroConfezioni
        FROM CONFEZIONE c
        JOIN PRODUZIONE pr ON c.idProduzione = pr.idProduzione
        WHERE c.idConfezione = ?" . ($mySedeId ? " AND pr.idSede = $mySedeId" : ""));
    $chk->execute([$id]); $conf = $chk->fetch();
    if (!$conf) {
        flash('error','Confezione non trovata o non autorizzata.');
    } elseif ($nuovaGiac < 0 || $nuovaGiac > $conf['numeroConfezioni']) {
        flash('error',"La giacenza deve essere tra 0 e {$conf['numeroConfezioni']}.");
    } else {
        $pdo->prepare("UPDATE CONFEZIONE SET giacenza=? WHERE idConfezione=?")->execute([$nuovaGiac,$id]);
        flash('success','Giacenza aggiornata.');
    }
    redirect("sedi.php?action=manage&id=$sedeBack");
}

// ── EDIT / CREATE SEDE ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action,['create','edit'])) {
    $nomeSede  = trim($_POST['nomeSede'] ?? '');
    $indirizzo = trim($_POST['indirizzo'] ?? '') ?: null;
    if (!$nomeSede) { flash('error','Il nome è obbligatorio.'); redirect("sedi.php?action=$action".($id?"&id=$id":'')); }
    if ($action === 'create') {
        $pdo->prepare("INSERT INTO SEDE (nomeSede,indirizzo) VALUES (?,?)")->execute([$nomeSede,$indirizzo]);
        flash('success',"Sede «$nomeSede» creata."); redirect('sedi.php');
    }
    if ($action === 'edit' && $id) {
        if ($mySedeId && $mySedeId !== $id) { flash('error','Non puoi modificare questa sede.'); redirect('sedi.php'); }
        $pdo->prepare("UPDATE SEDE SET nomeSede=?,indirizzo=? WHERE idSede=?")->execute([$nomeSede,$indirizzo,$id]);
        flash('success','Sede aggiornata.'); redirect('sedi.php');
    }
}

// ── DELETE ────────────────────────────────────────────────────
if ($action === 'delete' && $id && $isSuperA) {
    try { $pdo->prepare("DELETE FROM SEDE WHERE idSede=?")->execute([$id]); flash('success','Sede eliminata.');
    } catch (PDOException $e) { flash('error','Impossibile eliminare: ci sono dati associati.'); }
    redirect('sedi.php');
}

// ── FETCH FOR EDIT ────────────────────────────────────────────
$row = null;
if ($action === 'edit' && $id) {
    $s = $pdo->prepare("SELECT * FROM SEDE WHERE idSede=?"); $s->execute([$id]); $row = $s->fetch();
    if (!$row) { flash('error','Sede non trovata.'); redirect('sedi.php'); }
    if ($mySedeId && $mySedeId !== (int)$row['idSede']) { flash('error','Accesso negato.'); redirect('sedi.php'); }
}

// ── MANAGE: confezioni della sede ─────────────────────────────
$sedeManage = null; $confezioniSede = [];
if ($action === 'manage' && $id) {
    if ($mySedeId && $mySedeId !== $id) { flash('error','Accesso negato.'); redirect('sedi.php'); }
    $sm = $pdo->prepare("SELECT * FROM SEDE WHERE idSede=?"); $sm->execute([$id]); $sedeManage = $sm->fetch();
    if (!$sedeManage) { flash('error','Sede non trovata.'); redirect('sedi.php'); }
    $cq = $pdo->prepare("
        SELECT c.*, pp.nome AS prodotto, pp.unitaMisura
        FROM CONFEZIONE c
        JOIN PRODUZIONE pr ON c.idProduzione = pr.idProduzione
        JOIN PRODOTTO   pp ON pr.idProdottoProdotto = pp.idProdotto
        WHERE pr.idSede = ?
        ORDER BY c.giacenza ASC, c.dataConfezionamento DESC");
    $cq->execute([$id]); $confezioniSede = $cq->fetchAll();
}

// ── LIST ──────────────────────────────────────────────────────
$sedeWhere = $mySedeId ? "WHERE s.idSede=$mySedeId" : '';
$sedi = $pdo->query("
    SELECT s.*,
        (SELECT COUNT(*) FROM PRODUZIONE p WHERE p.idSede=s.idSede) AS nProd,
        (SELECT COUNT(*) FROM VENDITA    v WHERE v.idSede=s.idSede) AS nVend,
        (SELECT COALESCE(SUM(c.giacenza),0) FROM CONFEZIONE c JOIN PRODUZIONE pr ON c.idProduzione=pr.idProduzione WHERE pr.idSede=s.idSede) AS totGiac
    FROM SEDE s $sedeWhere ORDER BY s.nomeSede")->fetchAll();

$pageTitle  = match($action) {
    'create' => 'Nuova Sede', 'edit' => 'Modifica Sede',
    'manage' => 'Gestione: ' . ($sedeManage['nomeSede'] ?? ''), default => 'Sedi' };
$breadcrumb = $action !== 'list' ? [['label'=>'Sedi','url'=>'sedi.php'],['label'=>$pageTitle]] : null;
require_once 'includes/header.php';
?>

<?php if ($action === 'list'): ?>
<div class="page-header">
  <h5 class="page-header-title"><i class="fa-solid fa-location-dot"></i> Sedi (<?= count($sedi) ?>)</h5>
  <?php if ($isSuperA): ?>
    <a href="?action=create" class="btn-ag"><i class="fa-solid fa-plus"></i> Nuova Sede</a>
  <?php endif; ?>
</div>
<div class="ag-card">
  <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-map"></i> Elenco Sedi</h6></div>
  <div class="table-responsive">
    <?php if (empty($sedi)): ?>
      <div class="empty-state"><i class="fa-solid fa-location-dot"></i><p>Nessuna sede trovata.</p></div>
    <?php else: ?>
    <table class="ag-table table mb-0">
      <thead><tr><th>#</th><th>Nome Sede</th><th>Indirizzo</th><th>Admin</th><th>Produzioni</th><th>Vendite</th><th>Stock Totale</th><th class="text-end">Azioni</th></tr></thead>
      <tbody>
      <?php foreach ($sedi as $s): ?>
      <tr>
        <td class="text-muted"><?= $s['idSede'] ?></td>
        <td class="fw-semibold"><?= h($s['nomeSede']) ?></td>
        <td><small class="text-muted"><?= h($s['indirizzo'] ?? '—') ?></small></td>
        <td>
          <?php if ($s['admin_email']): ?>
            <span class="badge-ok" style="font-size:.72rem"><i class="fa-solid fa-shield-halved me-1"></i><?= h($s['admin_email']) ?></span>
          <?php else: ?>
            <span class="badge-low" style="font-size:.72rem">Non configurata</span>
          <?php endif; ?>
        </td>
        <td><span class="badge-ok"><?= $s['nProd'] ?></span></td>
        <td><span class="badge-ok"><?= $s['nVend'] ?></span></td>
        <td><span class="<?= $s['totGiac']>0?'badge-ok':'badge-zero' ?>"><?= $s['totGiac'] ?> pz</span></td>
        <td class="text-end">
          <a href="?action=manage&id=<?= $s['idSede'] ?>" class="btn-view-sm me-1" title="Gestisci giacenze">
            <i class="fa-solid fa-boxes-stacked"></i>
          </a>
          <a href="?action=edit&id=<?= $s['idSede'] ?>" class="btn-edit-sm me-1"><i class="fa-solid fa-pen"></i></a>
          <?php if ($isSuperA): ?>
          <a href="?action=delete&id=<?= $s['idSede'] ?>" class="btn-danger-sm"
             onclick="return confirm('Eliminare «<?= h($s['nomeSede']) ?>»?')"><i class="fa-solid fa-trash"></i></a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($action === 'manage' && $sedeManage): ?>
<!-- ══ GESTIONE GIACENZE ═════════════════════════════════════ -->
<div class="row g-3 mb-4">
  <!-- Info sede -->
  <div class="col-md-4">
    <div class="ag-card h-100">
      <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-location-dot"></i> Sede</h6></div>
      <div class="p-4">
        <div class="fw-bold mb-1" style="font-size:1.1rem"><?= h($sedeManage['nomeSede']) ?></div>
        <div class="text-muted mb-3" style="font-size:.85rem"><?= h($sedeManage['indirizzo'] ?? '—') ?></div>
        <?php if ($sedeManage['admin_email']): ?>
          <div class="badge-ok" style="font-size:.78rem;display:inline-flex;gap:6px;align-items:center">
            <i class="fa-solid fa-shield-halved"></i><?= h($sedeManage['admin_email']) ?>
          </div>
        <?php else: ?>
          <div class="badge-low" style="font-size:.78rem;display:inline-flex;gap:6px">
            <i class="fa-solid fa-triangle-exclamation"></i>Nessun admin configurato
          </div>
        <?php endif; ?>
        <div class="mt-3">
          <a href="?action=edit&id=<?= $sedeManage['idSede'] ?>" class="btn-ag-outline btn-ag-sm">
            <i class="fa-solid fa-pen"></i> Modifica Sede
          </a>
        </div>
      </div>
    </div>
  </div>
  <!-- Stats rapide -->
  <?php
  $totConf  = count($confezioniSede);
  $lowConf  = count(array_filter($confezioniSede, fn($c)=>$c['giacenza']>0 && $c['giacenza']<($c['numeroConfezioni']*0.2)));
  $zeroConf = count(array_filter($confezioniSede, fn($c)=>$c['giacenza']==0));
  $okConf   = $totConf - $lowConf - $zeroConf;
  ?>
  <div class="col-md-8">
    <div class="ag-card h-100">
      <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-chart-bar"></i> Stato Stock</h6></div>
      <div class="p-4">
        <div class="row g-3 text-center">
          <div class="col-4">
            <div style="background:var(--ag-pale);border-radius:14px;padding:18px 8px">
              <div style="font-size:2rem;font-weight:800;color:var(--ag-primary)"><?= $okConf ?></div>
              <div style="font-size:.78rem;color:var(--ag-text-muted)">Regolari</div>
            </div>
          </div>
          <div class="col-4">
            <div style="background:#fff7ed;border-radius:14px;padding:18px 8px">
              <div style="font-size:2rem;font-weight:800;color:#d97706"><?= $lowConf ?></div>
              <div style="font-size:.78rem;color:#d97706">Giacenza Bassa</div>
            </div>
          </div>
          <div class="col-4">
            <div style="background:#fee2e2;border-radius:14px;padding:18px 8px">
              <div style="font-size:2rem;font-weight:800;color:#dc2626"><?= $zeroConf ?></div>
              <div style="font-size:.78rem;color:#dc2626">Esauriti</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tabella giacenze inline -->
<div class="ag-card">
  <div class="ag-card-header">
    <h6 class="ag-card-title"><i class="fa-solid fa-boxes-stacked"></i> Modifica Giacenze</h6>
    <small class="text-muted">Modifica il valore e premi 💾 per salvare</small>
  </div>
  <div class="table-responsive">
    <?php if (empty($confezioniSede)): ?>
      <div class="empty-state"><i class="fa-solid fa-boxes-stacked"></i><p>Nessuna confezione per questa sede.</p></div>
    <?php else: ?>
    <table class="ag-table table mb-0">
      <thead><tr><th>#</th><th>Prodotto</th><th>Peso</th><th>Confez.</th><th>Giacenza Attuale</th><th>Livello</th><th>Aggiorna</th></tr></thead>
      <tbody>
      <?php foreach ($confezioniSede as $c):
        $pct  = $c['numeroConfezioni']>0 ? ($c['giacenza']/$c['numeroConfezioni']*100) : 0;
        $fc   = $c['giacenza']==0?'#dc2626':($pct<20?'#d97706':'#2D6A4F');
        $bc   = $c['giacenza']==0?'badge-zero':($pct<20?'badge-low':'badge-ok');
      ?>
      <tr>
        <td class="text-muted"><?= $c['idConfezione'] ?></td>
        <td class="fw-semibold"><?= h($c['prodotto']) ?></td>
        <td><span class="badge-ok"><?= $c['pesoNetto'] ?> <?= h($c['unitaMisura']) ?></span></td>
        <td><?= $c['numeroConfezioni'] ?></td>
        <td><span class="<?= $bc ?>"><?= $c['giacenza'] ?></span></td>
        <td style="min-width:100px">
          <div class="d-flex align-items-center gap-2">
            <div class="giacenza-bar flex-grow-1"><div class="giacenza-fill" style="width:<?= $pct ?>%;background:<?= $fc ?>"></div></div>
            <span style="font-size:.72rem;color:var(--ag-text-muted);width:32px"><?= round($pct) ?>%</span>
          </div>
        </td>
        <td>
          <form method="POST" action="?action=giacenza&id=<?= $c['idConfezione'] ?>" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="idSede" value="<?= $sedeManage['idSede'] ?>">
            <input type="number" name="giacenza" value="<?= $c['giacenza'] ?>"
                   min="0" max="<?= $c['numeroConfezioni'] ?>"
                   class="form-control form-control-sm" style="width:80px;text-align:center"
                   title="Max: <?= $c['numeroConfezioni'] ?>">
            <button type="submit" class="btn-ag btn-ag-sm" title="Salva">
              <i class="fa-solid fa-floppy-disk"></i>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php else: /* create / edit form */ ?>
<div class="ag-form-card">
  <div class="ag-form-header">
    <h5><i class="fa-solid fa-location-dot me-2"></i><?= h($pageTitle) ?></h5>
    <p>Gestisci le sedi operative dell'azienda.</p>
  </div>
  <div class="ag-form-body">
    <form method="POST" action="?action=<?= h($action) ?><?= $id?"&id=$id":'' ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nome Sede <span class="text-danger">*</span></label>
          <input type="text" name="nomeSede" class="form-control" required
                 value="<?= h($row['nomeSede']??'') ?>" placeholder="Es. Podere Nord">
        </div>
        <div class="col-md-6">
          <label class="form-label">Indirizzo</label>
          <input type="text" name="indirizzo" class="form-control"
                 value="<?= h($row['indirizzo']??'') ?>" placeholder="Via, Città...">
        </div>
      </div>

      <?php if ($action === 'edit' && !empty($row['admin_email'])): ?>
      <div class="mt-4 p-3" style="background:var(--ag-pale);border-radius:12px;border:1px solid var(--ag-border)">
        <div style="font-size:.85rem"><i class="fa-solid fa-shield-halved me-1" style="color:var(--ag-primary)"></i>
          <strong>Admin configurato:</strong> <?= h($row['admin_email']) ?><br>
          <small class="text-muted">Per cambiare la password admin aggiorna <code>admin_password_hash</code> direttamente via phpMyAdmin con un hash bcrypt.</small>
        </div>
      </div>
      <?php endif; ?>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn-ag"><i class="fa-solid fa-floppy-disk"></i> Salva</button>
        <a href="sedi.php" class="btn-ag-outline"><i class="fa-solid fa-arrow-left"></i> Annulla</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>