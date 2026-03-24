<?php
require_once 'includes/auth.php';
$currentPage = 'categorie';
$basePath    = '';
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nomeCategoria'] ?? '');
    if (!$nome) { flash('error','Il nome è obbligatorio.'); redirect("categorie.php?action=$action".($id?"&id=$id":'')); }
    if ($action === 'create') {
        $pdo->prepare("INSERT INTO CATEGORIA (nomeCategoria) VALUES (?)")->execute([$nome]);
        flash('success',"Categoria «$nome» creata."); redirect('categorie.php');
    }
    if ($action === 'edit' && $id) {
        $pdo->prepare("UPDATE CATEGORIA SET nomeCategoria=? WHERE idCategoria=?")->execute([$nome,$id]);
        flash('success','Categoria aggiornata.'); redirect('categorie.php');
    }
}
if ($action === 'delete' && $id) {
    try { $pdo->prepare("DELETE FROM CATEGORIA WHERE idCategoria=?")->execute([$id]); flash('success','Categoria eliminata.');
    } catch (PDOException) { flash('error','Impossibile eliminare: ci sono prodotti associati.'); }
    redirect('categorie.php');
}
$row = null;
if ($action === 'edit' && $id) {
    $s = $pdo->prepare("SELECT * FROM CATEGORIA WHERE idCategoria=?"); $s->execute([$id]); $row = $s->fetch();
    if (!$row) { flash('error','Non trovata.'); redirect('categorie.php'); }
}
$categorie = $pdo->query("
    SELECT c.*, COUNT(p.idProdotto) AS nProdotti
    FROM CATEGORIA c LEFT JOIN PRODOTTO p ON p.idCategoria=c.idCategoria
    GROUP BY c.idCategoria ORDER BY c.nomeCategoria
")->fetchAll();

$pageTitle  = match($action){'create'=>'Nuova Categoria','edit'=>'Modifica Categoria',default=>'Categorie'};
$breadcrumb = $action!=='list'?[['label'=>'Categorie','url'=>'categorie.php'],['label'=>$pageTitle]]:null;
require_once 'includes/header.php';
?>
<?php if ($action==='list'): ?>
<div class="page-header">
  <h5 class="page-header-title"><i class="fa-solid fa-tags"></i> Categorie (<?= count($categorie) ?>)</h5>
  <a href="?action=create" class="btn-ag"><i class="fa-solid fa-plus"></i> Nuova Categoria</a>
</div>
<div class="ag-card">
  <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-tags"></i> Categorie Merceologiche</h6></div>
  <div class="table-responsive">
    <?php if(empty($categorie)): ?>
      <div class="empty-state"><i class="fa-solid fa-tags"></i><p>Nessuna categoria.</p></div>
    <?php else: ?>
    <table class="ag-table table mb-0">
      <thead><tr><th>#</th><th>Nome Categoria</th><th>Prodotti</th><th class="text-end">Azioni</th></tr></thead>
      <tbody>
      <?php foreach($categorie as $c): ?>
      <tr>
        <td class="text-muted"><?= $c['idCategoria'] ?></td>
        <td class="fw-semibold"><?= h($c['nomeCategoria']) ?></td>
        <td><?= $c['nProdotti']>0?"<a href='prodotti.php?categoria={$c['idCategoria']}' class='badge-ok text-decoration-none'>{$c['nProdotti']} prodott".($c['nProdotti']!=1?'i':'o')."</a>":'<span class="text-muted">—</span>' ?></td>
        <td class="text-end">
          <a href="?action=edit&id=<?= $c['idCategoria'] ?>" class="btn-edit-sm me-1"><i class="fa-solid fa-pen"></i></a>
          <a href="?action=delete&id=<?= $c['idCategoria'] ?>" class="btn-danger-sm" onclick="return confirm('Eliminare «<?= h($c['nomeCategoria']) ?>»?')"><i class="fa-solid fa-trash"></i></a>
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
    <h5><i class="fa-solid fa-tag me-2"></i><?= h($pageTitle) ?></h5>
    <p>Classifica i prodotti per tipologia merceologica.</p>
  </div>
  <div class="ag-form-body">
    <form method="POST" action="?action=<?= h($action) ?><?= $id?"&id=$id":'' ?>">
      <div class="col-md-6">
        <label class="form-label">Nome Categoria <span class="text-danger">*</span></label>
        <input type="text" name="nomeCategoria" class="form-control" required
               value="<?= h($row['nomeCategoria']??'') ?>" placeholder="Es. Miele e Derivati">
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn-ag"><i class="fa-solid fa-floppy-disk"></i> Salva</button>
        <a href="categorie.php" class="btn-ag-outline"><i class="fa-solid fa-arrow-left"></i> Annulla</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
