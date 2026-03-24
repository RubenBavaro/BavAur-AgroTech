<?php
require_once 'includes/auth.php';
$currentPage = 'confezioni';
$basePath    = '';
$action     = $_GET['action'] ?? 'list';
$id         = isset($_GET['id'])         ? (int)$_GET['id']         : null;
$prodFilter = isset($_GET['produzione']) ? (int)$_GET['produzione'] : null;
$mySedeId   = userSede();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data  = $_POST['dataConfezionamento'] ?? '';
    $peso  = (float)($_POST['pesoNetto']        ?? 0);
    $nConf = (int)($_POST['numeroConfezioni']   ?? 0);
    $giac  = (int)($_POST['giacenza']           ?? 0);
    $idP   = (int)($_POST['idProduzione']       ?? 0);
    if (!$data || $peso<=0 || $nConf<=0 || $giac<0 || $giac>$nConf || !$idP) {
        flash('error','Controlla i dati. La giacenza deve essere ≥ 0 e ≤ numero confezioni.');
        redirect("confezioni.php?action=$action".($id?"&id=$id":''));
    }
    if ($action === 'create') {
        $pdo->prepare("INSERT INTO CONFEZIONE (dataConfezionamento,pesoNetto,numeroConfezioni,giacenza,idProduzione) VALUES (?,?,?,?,?)")
            ->execute([$data,$peso,$nConf,$giac,$idP]);
        flash('success','Confezione registrata.'); redirect('confezioni.php');
    }
    if ($action === 'edit' && $id) {
        $pdo->prepare("UPDATE CONFEZIONE SET dataConfezionamento=?,pesoNetto=?,numeroConfezioni=?,giacenza=?,idProduzione=? WHERE idConfezione=?")
            ->execute([$data,$peso,$nConf,$giac,$idP,$id]);
        flash('success','Confezione aggiornata.'); redirect('confezioni.php');
    }
}
if ($action === 'delete' && $id) {
    try { $pdo->prepare("DELETE FROM CONFEZIONE WHERE idConfezione=?")->execute([$id]); flash('success','Confezione eliminata.');
    } catch (PDOException) { flash('error','Impossibile eliminare: ha vendite associate.'); }
    redirect('confezioni.php');
}
$row = null;
if ($action === 'edit' && $id) {
    $s = $pdo->prepare("SELECT * FROM CONFEZIONE WHERE idConfezione=?"); $s->execute([$id]); $row = $s->fetch();
    if (!$row) { flash('error','Non trovata.'); redirect('confezioni.php'); }
}
$produzioni = $pdo->query("
    SELECT pr.idProduzione, pr.dataProduzione, pp.nome AS prodotto, pp.unitaMisura
    " . ($mySedeId ? ", s.nomeSede" : "") . "
    FROM PRODUZIONE pr
    JOIN PRODOTTO pp ON pr.idProdottoProdotto=pp.idProdotto
    " . ($mySedeId ? "JOIN SEDE s ON pr.idSede=s.idSede WHERE pr.idSede=$mySedeId" : "") . "
    ORDER BY pr.dataProduzione DESC
")->fetchAll();

// Lista confezioni con filtro sede e produzione
$wheres = [];
if ($prodFilter) $wheres[] = "c.idProduzione=$prodFilter";
if ($mySedeId)   $wheres[] = "pr.idSede=$mySedeId";
$where = $wheres ? "WHERE ".implode(' AND ',$wheres) : '';
$confezioni = $pdo->query("
    SELECT c.*, pp.nome AS prodotto, pp.unitaMisura, pr.dataProduzione
    FROM CONFEZIONE c
    JOIN PRODUZIONE pr ON c.idProduzione=pr.idProduzione
    JOIN PRODOTTO   pp ON pr.idProdottoProdotto=pp.idProdotto
    $where ORDER BY c.dataConfezionamento DESC
")->fetchAll();

$pageTitle  = match($action){'create'=>'Nuova Confezione','edit'=>'Modifica Confezione',default=>'Confezioni'};
$breadcrumb = $action!=='list'?[['label'=>'Confezioni','url'=>'confezioni.php'],['label'=>$pageTitle]]:null;
require_once 'includes/header.php';
?>
<?php if ($action==='list'): ?>
<div class="page-header">
  <h5 class="page-header-title"><i class="fa-solid fa-box-open"></i> Confezioni (<?= count($confezioni) ?>)</h5>
  <a href="?action=create" class="btn-ag"><i class="fa-solid fa-plus"></i> Nuova Confezione</a>
</div>
<div class="ag-card">
  <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-boxes-stacked"></i> Lotti Confezionati</h6></div>
  <div class="table-responsive">
    <?php if(empty($confezioni)): ?>
      <div class="empty-state"><i class="fa-solid fa-box-open"></i><p>Nessuna confezione registrata.</p></div>
    <?php else: ?>
    <table class="ag-table table mb-0">
      <thead><tr><th>#</th><th>Data</th><th>Prodotto</th><th>Peso/Unità</th><th>N. Conf.</th><th>Giacenza</th><th>Livello</th><th class="text-end">Azioni</th></tr></thead>
      <tbody>
      <?php foreach($confezioni as $c):
        $pct  = $c['numeroConfezioni']>0 ? ($c['giacenza']/$c['numeroConfezioni']*100) : 0;
        $fill = $c['giacenza']==0?'#dc2626':($pct<20?'#d97706':'#2D6A4F');
        $bc   = $c['giacenza']==0?'badge-zero':($pct<20?'badge-low':'badge-ok');
      ?>
      <tr>
        <td class="text-muted"><?= $c['idConfezione'] ?></td>
        <td><?= date('d/m/Y',strtotime($c['dataConfezionamento'])) ?></td>
        <td class="fw-semibold"><?= h($c['prodotto']) ?></td>
        <td><span class="badge-ok"><?= h($c['pesoNetto']) ?> <?= h($c['unitaMisura']) ?></span></td>
        <td><?= $c['numeroConfezioni'] ?></td>
        <td><span class="<?= $bc ?>"><?= $c['giacenza']==0?'Esaurito':$c['giacenza'] ?></span></td>
        <td style="min-width:130px">
          <div class="d-flex align-items-center gap-2">
            <div class="giacenza-bar flex-grow-1"><div class="giacenza-fill" style="width:<?= $pct ?>%;background:<?= $fill ?>"></div></div>
            <span style="font-size:.72rem;color:var(--ag-text-muted);width:32px"><?= round($pct) ?>%</span>
          </div>
        </td>
        <td class="text-end">
          <a href="?action=edit&id=<?= $c['idConfezione'] ?>" class="btn-edit-sm me-1"><i class="fa-solid fa-pen"></i></a>
          <a href="?action=delete&id=<?= $c['idConfezione'] ?>" class="btn-danger-sm" onclick="return confirm('Eliminare questa confezione?')"><i class="fa-solid fa-trash"></i></a>
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
    <h5><i class="fa-solid fa-box-open me-2"></i><?= h($pageTitle) ?></h5>
    <p>Registra un lotto di prodotto confezionato pronto per la vendita.</p>
  </div>
  <div class="ag-form-body">
    <form method="POST" action="?action=<?= h($action) ?><?= $id?"&id=$id":'' ?>">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Produzione di origine <span class="text-danger">*</span></label>
          <select name="idProduzione" class="form-select" required>
            <option value="">Seleziona produzione...</option>
            <?php foreach($produzioni as $p): ?>
              <option value="<?= $p['idProduzione'] ?>" <?= ($row['idProduzione']??'')==$p['idProduzione']?'selected':'' ?>>
                #<?= $p['idProduzione'] ?> — <?= h($p['prodotto']) ?> (<?= date('d/m/Y',strtotime($p['dataProduzione'])) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Data Confezionamento <span class="text-danger">*</span></label>
          <input type="date" name="dataConfezionamento" class="form-control" required value="<?= h($row['dataConfezionamento']??date('Y-m-d')) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Peso Netto (kg/litri) <span class="text-danger">*</span></label>
          <input type="number" name="pesoNetto" class="form-control" step="0.001" min="0.001" required value="<?= h($row['pesoNetto']??'') ?>" placeholder="Es. 0.500">
        </div>
        <div class="col-md-3">
          <label class="form-label">N. Confezioni <span class="text-danger">*</span></label>
          <input type="number" name="numeroConfezioni" class="form-control" min="1" required id="nConf"
                 value="<?= h($row['numeroConfezioni']??'') ?>" placeholder="Es. 50" oninput="document.getElementById('giac').max=this.value">
        </div>
        <div class="col-md-3">
          <label class="form-label">Giacenza <span class="text-danger">*</span></label>
          <input type="number" name="giacenza" class="form-control" min="0" required id="giac"
                 value="<?= h($row['giacenza']??'') ?>" placeholder="Es. 50">
          <small class="text-muted">Deve essere ≤ N. Confezioni</small>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn-ag"><i class="fa-solid fa-floppy-disk"></i> Salva</button>
        <a href="confezioni.php" class="btn-ag-outline"><i class="fa-solid fa-arrow-left"></i> Annulla</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
