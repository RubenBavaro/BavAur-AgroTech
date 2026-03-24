<?php
require_once 'includes/auth.php';
$currentPage = 'clienti';
$basePath    = '';
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']     ?? '');
    $nickname = trim($_POST['nickname'] ?? '') ?: null;
    $contatti = trim($_POST['contatti'] ?? '') ?: null;
    if (!$nome) { flash('error','Il nome è obbligatorio.'); redirect("clienti.php?action=$action".($id?"&id=$id":'')); }
    if ($action === 'create') {
        $pdo->prepare("INSERT INTO CLIENTE (nome,nickname,contatti) VALUES (?,?,?)")->execute([$nome,$nickname,$contatti]);
        flash('success',"Cliente «$nome» creato."); redirect('clienti.php');
    }
    if ($action === 'edit' && $id) {
        $pdo->prepare("UPDATE CLIENTE SET nome=?,nickname=?,contatti=? WHERE idCliente=?")->execute([$nome,$nickname,$contatti,$id]);
        flash('success',"Cliente aggiornato."); redirect('clienti.php');
    }
}
if ($action === 'delete' && $id) {
    try { $pdo->prepare("DELETE FROM CLIENTE WHERE idCliente=?")->execute([$id]); flash('success','Cliente eliminato.');
    } catch (PDOException) { flash('error','Impossibile eliminare: ha vendite associate.'); }
    redirect('clienti.php');
}
$row = null;
if (in_array($action,['edit']) && $id) {
    $s = $pdo->prepare("SELECT * FROM CLIENTE WHERE idCliente=?"); $s->execute([$id]); $row = $s->fetch();
    if (!$row) { flash('error','Non trovato.'); redirect('clienti.php'); }
}
$clienti = $pdo->query("
    SELECT c.*, COUNT(v.idVendita) AS nVendite, COALESCE(SUM(v.totalePagato),0) AS totSpeso
    FROM CLIENTE c LEFT JOIN VENDITA v ON v.idCliente=c.idCliente
    GROUP BY c.idCliente ORDER BY c.nome
")->fetchAll();

$pageTitle  = match($action){'create'=>'Nuovo Cliente','edit'=>'Modifica Cliente',default=>'Clienti'};
$breadcrumb = $action!=='list'?[['label'=>'Clienti','url'=>'clienti.php'],['label'=>$pageTitle]]:null;
require_once 'includes/header.php';
?>
<?php if ($action==='list'): ?>
<div class="page-header">
  <h5 class="page-header-title"><i class="fa-solid fa-users"></i> Clienti (<?= count($clienti) ?>)</h5>
  <a href="?action=create" class="btn-ag"><i class="fa-solid fa-plus"></i> Nuovo Cliente</a>
</div>
<div class="ag-card">
  <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-list"></i> Anagrafica Clienti</h6></div>
  <div class="table-responsive">
    <?php if(empty($clienti)): ?>
      <div class="empty-state"><i class="fa-solid fa-users"></i><p>Nessun cliente.<br><a href="?action=create">Aggiungi il primo</a></p></div>
    <?php else: ?>
    <table class="ag-table table mb-0">
      <thead><tr><th>#</th><th>Nome</th><th>Nickname</th><th>Contatti</th><th>Vendite</th><th>Totale Speso</th><th class="text-end">Azioni</th></tr></thead>
      <tbody>
      <?php foreach($clienti as $c): ?>
      <tr>
        <td class="text-muted"><?= $c['idCliente'] ?></td>
        <td class="fw-semibold"><?= h($c['nome']) ?></td>
        <td><?= $c['nickname']?'<span class="badge-ok">'.h($c['nickname']).'</span>':'<span class="text-muted">—</span>' ?></td>
        <td><small class="text-muted" style="white-space:pre-line"><?= h($c['contatti']??'—') ?></small></td>
        <td><?= $c['nVendite']>0?"<a href='vendite.php?cliente={$c['idCliente']}' class='badge-ok text-decoration-none'>{$c['nVendite']} vendita".($c['nVendite']!=1?'e':'')."</a>":'<span class="text-muted">—</span>' ?></td>
        <td class="fw-semibold" style="color:var(--ag-primary)"><?= $c['totSpeso']>0?'€'.number_format($c['totSpeso'],2,',','.'):'<span class="text-muted">—</span>' ?></td>
        <td class="text-end">
          <a href="?action=edit&id=<?= $c['idCliente'] ?>" class="btn-edit-sm me-1"><i class="fa-solid fa-pen"></i></a>
          <a href="?action=delete&id=<?= $c['idCliente'] ?>" class="btn-danger-sm" onclick="return confirm('Eliminare «<?= h($c['nome']) ?>»?')"><i class="fa-solid fa-trash"></i></a>
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
    <h5><i class="fa-solid fa-<?= $action==='create'?'user-plus':'pen' ?> me-2"></i><?= h($pageTitle) ?></h5>
    <p><?= $action==='create'?'Registra un nuovo cliente.':'Modifica i dati del cliente.' ?></p>
  </div>
  <div class="ag-form-body">
    <form method="POST" action="?action=<?= h($action) ?><?= $id?"&id=$id":'' ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nome e Cognome <span class="text-danger">*</span></label>
          <input type="text" name="nome" class="form-control" required value="<?= h($row['nome']??'') ?>" placeholder="Mario Rossi">
        </div>
        <div class="col-md-6">
          <label class="form-label">Nickname</label>
          <input type="text" name="nickname" class="form-control" value="<?= h($row['nickname']??'') ?>" placeholder="Soprannome (opzionale)">
        </div>
        <div class="col-12">
          <label class="form-label">Contatti</label>
          <textarea name="contatti" class="form-control" rows="3" placeholder="Telefono, email, indirizzo..."><?= h($row['contatti']??'') ?></textarea>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn-ag"><i class="fa-solid fa-floppy-disk"></i> Salva</button>
        <a href="clienti.php" class="btn-ag-outline"><i class="fa-solid fa-arrow-left"></i> Annulla</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
