<?php
require_once 'includes/auth.php';
$currentPage = 'produzioni';
$basePath    = '';
$action    = $_GET['action'] ?? 'list';
$id        = isset($_GET['id']) ? (int)$_GET['id'] : null;
$mySedeId  = userSede();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data     = $_POST['dataProduzione']     ?? '';
    $quantita = (float)($_POST['quantitaProdotta']   ?? 0);
    $idProd   = (int)($_POST['idProdottoProdotto']   ?? 0);
    $idLav    = (int)($_POST['idProdottoLavorato']   ?? 0);
    $idSede   = $mySedeId ?? (int)($_POST['idSede']  ?? 0);
    if (!$data || $quantita <= 0 || !$idProd || !$idLav || !$idSede) {
        flash('error','Compila tutti i campi obbligatori correttamente.');
        redirect("produzioni.php?action=$action".($id?"&id=$id":''));
    }
    if ($action === 'create') {
        $pdo->prepare("INSERT INTO PRODUZIONE (dataProduzione,quantitaProdotta,idProdottoProdotto,idProdottoLavorato,idSede) VALUES (?,?,?,?,?)")
            ->execute([$data,$quantita,$idProd,$idLav,$idSede]);
        flash('success','Produzione registrata.'); redirect('produzioni.php');
    }
    if ($action === 'edit' && $id) {
        $pdo->prepare("UPDATE PRODUZIONE SET dataProduzione=?,quantitaProdotta=?,idProdottoProdotto=?,idProdottoLavorato=?,idSede=? WHERE idProduzione=?")
            ->execute([$data,$quantita,$idProd,$idLav,$idSede,$id]);
        flash('success','Produzione aggiornata.'); redirect('produzioni.php');
    }
}
if ($action === 'delete' && $id) {
    try { $pdo->prepare("DELETE FROM PRODUZIONE WHERE idProduzione=?")->execute([$id]); flash('success','Produzione eliminata.');
    } catch (PDOException) { flash('error','Impossibile eliminare: ha confezioni associate.'); }
    redirect('produzioni.php');
}
$row = null;
if ($action === 'edit' && $id) {
    $s = $pdo->prepare("SELECT * FROM PRODUZIONE WHERE idProduzione=?"); $s->execute([$id]); $row = $s->fetch();
    if (!$row) { flash('error','Non trovata.'); redirect('produzioni.php'); }
    if ($mySedeId && $mySedeId !== (int)$row['idSede']) { flash('error','Accesso negato.'); redirect('produzioni.php'); }
}
$prodotti = $pdo->query("SELECT * FROM PRODOTTO ORDER BY nome")->fetchAll();
$sedi     = $pdo->query("SELECT * FROM SEDE ORDER BY nomeSede")->fetchAll();

$prodWhere = $mySedeId ? "WHERE pr.idSede=$mySedeId" : '';
$produzioni = $pdo->query("
    SELECT pr.*, pp.nome AS prodottoProdotto, pp.unitaMisura,
           pl.nome AS prodottoLavorato, s.nomeSede,
           COUNT(c.idConfezione) AS nConfezioni
    FROM PRODUZIONE pr
    JOIN PRODOTTO pp ON pr.idProdottoProdotto=pp.idProdotto
    JOIN PRODOTTO pl ON pr.idProdottoLavorato=pl.idProdotto
    JOIN SEDE     s  ON pr.idSede=s.idSede
    LEFT JOIN CONFEZIONE c ON c.idProduzione=pr.idProduzione
    $prodWhere GROUP BY pr.idProduzione ORDER BY pr.dataProduzione DESC
")->fetchAll();

$pageTitle  = match($action){'create'=>'Nuova Produzione','edit'=>'Modifica Produzione',default=>'Produzioni'};
$breadcrumb = $action!=='list'?[['label'=>'Produzioni','url'=>'produzioni.php'],['label'=>$pageTitle]]:null;
require_once 'includes/header.php';
?>
<?php if ($action==='list'): ?>
<div class="page-header">
  <h5 class="page-header-title"><i class="fa-solid fa-gears"></i> Produzioni (<?= count($produzioni) ?>)</h5>
  <a href="?action=create" class="btn-ag"><i class="fa-solid fa-plus"></i> Nuova Produzione</a>
</div>
<div class="ag-card">
  <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-gears"></i> Registro Produzioni</h6></div>
  <div class="table-responsive">
    <?php if(empty($produzioni)): ?>
      <div class="empty-state"><i class="fa-solid fa-gears"></i><p>Nessuna produzione registrata.</p></div>
    <?php else: ?>
    <table class="ag-table table mb-0">
      <thead><tr><th>#</th><th>Data</th><th>Prodotto Ottenuto</th><th>Materia Prima</th><th>Quantità</th><th>Sede</th><th>Confezioni</th><th class="text-end">Azioni</th></tr></thead>
      <tbody>
      <?php foreach($produzioni as $p): ?>
      <tr>
        <td class="text-muted"><?= $p['idProduzione'] ?></td>
        <td><?= date('d/m/Y',strtotime($p['dataProduzione'])) ?></td>
        <td class="fw-semibold"><?= h($p['prodottoProdotto']) ?></td>
        <td><small class="text-muted"><?= h($p['prodottoLavorato']) ?></small></td>
        <td><span class="badge-ok"><?= number_format($p['quantitaProdotta'],1) ?> <?= h($p['unitaMisura']) ?></span></td>
        <td><small class="text-muted"><?= h($p['nomeSede']) ?></small></td>
        <td><?= $p['nConfezioni']>0?"<a href='confezioni.php?produzione={$p['idProduzione']}' class='badge-ok text-decoration-none'>{$p['nConfezioni']} lotto".($p['nConfezioni']!=1?'i':'')."</a>":'<span class="text-muted">—</span>' ?></td>
        <td class="text-end">
          <a href="?action=edit&id=<?= $p['idProduzione'] ?>" class="btn-edit-sm me-1"><i class="fa-solid fa-pen"></i></a>
          <a href="?action=delete&id=<?= $p['idProduzione'] ?>" class="btn-danger-sm" onclick="return confirm('Eliminare questa produzione?')"><i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>
<div class="ag-form-card">
  <div class="ag-form-header">
    <h5><i class="fa-solid fa-gears me-2"></i><?= h($pageTitle) ?></h5>
    <p>Registra un ciclo produttivo con prodotto ottenuto e materia prima.</p>
  </div>
  <div class="ag-form-body">
    <form method="POST" action="?action=<?= h($action) ?><?= $id?"&id=$id":'' ?>">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Data Produzione <span class="text-danger">*</span></label>
          <input type="date" name="dataProduzione" class="form-control" required value="<?= h($row['dataProduzione']??date('Y-m-d')) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Quantità Prodotta <span class="text-danger">*</span></label>
          <input type="number" name="quantitaProdotta" class="form-control" step="0.01" min="0.01" required value="<?= h($row['quantitaProdotta']??'') ?>" placeholder="Es. 50.00">
        </div>
        <div class="col-md-4">
          <label class="form-label">Sede <span class="text-danger">*</span></label>
          <?php if ($mySedeId): ?>
            <?php $s=array_filter($sedi,fn($x)=>$x['idSede']===$mySedeId); $s=reset($s); ?>
            <input type="text" class="form-control" value="<?= h($s['nomeSede']??'') ?>" readonly>
            <input type="hidden" name="idSede" value="<?= $mySedeId ?>">
          <?php else: ?>
            <select name="idSede" class="form-select" required>
              <option value="">Seleziona sede...</option>
              <?php foreach($sedi as $s): ?>
                <option value="<?= $s['idSede'] ?>" <?= ($row['idSede']??'')==$s['idSede']?'selected':'' ?>><?= h($s['nomeSede']) ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">
            <span style="color:var(--ag-primary)">→</span> Prodotto Ottenuto (VIENE PRODOTTO) <span class="text-danger">*</span>
          </label>
          <select name="idProdottoProdotto" class="form-select" required>
            <option value="">Seleziona il prodotto risultante...</option>
            <?php foreach($prodotti as $p): ?>
              <option value="<?= $p['idProdotto'] ?>" <?= ($row['idProdottoProdotto']??'')==$p['idProdotto']?'selected':'' ?>>
                <?= h($p['nome']) ?> (<?= h($p['tipoProdotto']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Il prodotto generato da questa produzione.</small>
        </div>
        <div class="col-md-6">
          <label class="form-label">
            <span style="color:#d97706">←</span> Materia Prima (VIENE LAVORATO) <span class="text-danger">*</span>
          </label>
          <select name="idProdottoLavorato" class="form-select" required>
            <option value="">Seleziona la materia prima...</option>
            <?php foreach($prodotti as $p): ?>
              <option value="<?= $p['idProdotto'] ?>" <?= ($row['idProdottoLavorato']??'')==$p['idProdotto']?'selected':'' ?>>
                <?= h($p['nome']) ?> (<?= h($p['tipoProdotto']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Il prodotto utilizzato come ingrediente.</small>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn-ag"><i class="fa-solid fa-floppy-disk"></i> Salva</button>
        <a href="produzioni.php" class="btn-ag-outline"><i class="fa-solid fa-arrow-left"></i> Annulla</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
