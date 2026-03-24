<?php
require_once 'includes/auth.php';
$currentPage = 'prodotti';
$basePath    = '';
$action    = $_GET['action'] ?? 'list';
$id        = isset($_GET['id'])        ? (int)$_GET['id']        : null;
$catFilter = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome   = trim($_POST['nome'] ?? '');
    $unita  = $_POST['unitaMisura']  ?? '';
    $tipo   = $_POST['tipoProdotto'] ?? '';
    $idCat  = !empty($_POST['idCategoria']) ? (int)$_POST['idCategoria'] : null;
    $desc   = trim($_POST['descrizione'] ?? '') ?: null;
    if (!$nome || !$unita || !$tipo) { flash('error','Compila tutti i campi obbligatori.'); redirect("prodotti.php?action=$action".($id?"&id=$id":'')); }
    if ($action === 'create') {
        $pdo->prepare("INSERT INTO PRODOTTO (nome,unitaMisura,tipoProdotto,descrizione,idCategoria) VALUES (?,?,?,?,?)")->execute([$nome,$unita,$tipo,$desc,$idCat]);
        flash('success',"Prodotto «$nome» creato."); redirect('prodotti.php');
    }
    if ($action === 'edit' && $id) {
        $pdo->prepare("UPDATE PRODOTTO SET nome=?,unitaMisura=?,tipoProdotto=?,descrizione=?,idCategoria=? WHERE idProdotto=?")->execute([$nome,$unita,$tipo,$desc,$idCat,$id]);
        flash('success','Prodotto aggiornato.'); redirect('prodotti.php');
    }
}
if ($action === 'delete' && $id) {
    try { $pdo->prepare("DELETE FROM PRODOTTO WHERE idProdotto=?")->execute([$id]); flash('success','Prodotto eliminato.');
    } catch (PDOException) { flash('error','Impossibile eliminare: ha dati associati.'); }
    redirect('prodotti.php');
}
$row = null;
if ($action === 'edit' && $id) {
    $s = $pdo->prepare("SELECT * FROM PRODOTTO WHERE idProdotto=?"); $s->execute([$id]); $row = $s->fetch();
    if (!$row) { flash('error','Non trovato.'); redirect('prodotti.php'); }
}
$categorie = $pdo->query("SELECT * FROM CATEGORIA ORDER BY nomeCategoria")->fetchAll();

// List con filtro categoria
$sql = "SELECT p.*, c.nomeCategoria,
               (SELECT COALESCE(SUM(cf.giacenza),0) FROM CONFEZIONE cf JOIN PRODUZIONE pr ON cf.idProduzione=pr.idProduzione WHERE pr.idProdottoProdotto=p.idProdotto) AS totGiacenza
        FROM PRODOTTO p LEFT JOIN CATEGORIA c ON p.idCategoria=c.idCategoria";
$params = [];
if ($catFilter) { $sql .= " WHERE p.idCategoria=?"; $params[] = $catFilter; }
$sql .= " ORDER BY p.nome";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$prodotti = $stmt->fetchAll();

$pageTitle  = match($action){'create'=>'Nuovo Prodotto','edit'=>'Modifica Prodotto',default=>'Prodotti'};
$breadcrumb = $action!=='list'?[['label'=>'Prodotti','url'=>'prodotti.php'],['label'=>$pageTitle]]:null;
require_once 'includes/header.php';
?>
<?php if ($action==='list'): ?>
<div class="page-header">
  <h5 class="page-header-title"><i class="fa-solid fa-seedling"></i> Prodotti (<?= count($prodotti) ?>)</h5>
  <div class="d-flex gap-2 flex-wrap align-items-center">
    <form method="GET" class="d-flex gap-2 align-items-center">
      <select name="categoria" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
        <option value="">Tutte le categorie</option>
        <?php foreach($categorie as $c): ?>
          <option value="<?= $c['idCategoria'] ?>" <?= $catFilter==$c['idCategoria']?'selected':'' ?>><?= h($c['nomeCategoria']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <a href="?action=create" class="btn-ag"><i class="fa-solid fa-plus"></i> Nuovo Prodotto</a>
  </div>
</div>
<div class="ag-card">
  <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-seedling"></i> Catalogo Prodotti</h6></div>
  <div class="table-responsive">
    <?php if(empty($prodotti)): ?>
      <div class="empty-state"><i class="fa-solid fa-seedling"></i><p>Nessun prodotto trovato.</p></div>
    <?php else: ?>
    <table class="ag-table table mb-0">
      <thead><tr><th>#</th><th>Nome</th><th>Tipo</th><th>Unità</th><th>Categoria</th><th>Stock Confezioni</th><th>Descrizione</th><th class="text-end">Azioni</th></tr></thead>
      <tbody>
      <?php foreach($prodotti as $p): ?>
      <tr>
        <td class="text-muted"><?= $p['idProdotto'] ?></td>
        <td class="fw-semibold"><?= h($p['nome']) ?></td>
        <td><span class="badge-<?= $p['tipoProdotto'] ?>"><?= ucfirst($p['tipoProdotto']) ?></span></td>
        <td><span class="badge-ok"><?= h($p['unitaMisura']) ?></span></td>
        <td><small class="text-muted"><?= h($p['nomeCategoria']??'—') ?></small></td>
        <td>
          <?php if ($p['tipoProdotto']==='lavorato'): ?>
            <?php $g=(int)$p['totGiacenza']; ?>
            <span class="<?= $g==0?'badge-zero':($g<=10?'badge-low':'badge-ok') ?>">
              <?= $g==0?'Esaurito':"$g pz" ?>
            </span>
          <?php else: ?>
            <span class="badge-ok">Fresco</span>
          <?php endif; ?>
        </td>
        <td><small class="text-muted" style="display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;max-width:180px"><?= h($p['descrizione']??'—') ?></small></td>
        <td class="text-end">
          <a href="?action=edit&id=<?= $p['idProdotto'] ?>" class="btn-edit-sm me-1"><i class="fa-solid fa-pen"></i></a>
          <a href="?action=delete&id=<?= $p['idProdotto'] ?>" class="btn-danger-sm" onclick="return confirm('Eliminare «<?= h($p['nome']) ?>»?')"><i class="fa-solid fa-trash"></i></a>
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
    <h5><i class="fa-solid fa-seedling me-2"></i><?= h($pageTitle) ?></h5>
    <p>Aggiungi o modifica un prodotto del catalogo aziendale.</p>
  </div>
  <div class="ag-form-body">
    <form method="POST" action="?action=<?= h($action) ?><?= $id?"&id=$id":'' ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nome Prodotto <span class="text-danger">*</span></label>
          <input type="text" name="nome" class="form-control" required value="<?= h($row['nome']??'') ?>" placeholder="Es. Miele di Acacia">
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo <span class="text-danger">*</span></label>
          <select name="tipoProdotto" class="form-select" required>
            <option value="">Seleziona...</option>
            <option value="fresco"   <?= ($row['tipoProdotto']??'')==='fresco'  ?'selected':'' ?>>🥬 Fresco</option>
            <option value="lavorato" <?= ($row['tipoProdotto']??'')==='lavorato'?'selected':'' ?>>🍯 Lavorato</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Unità di Misura <span class="text-danger">*</span></label>
          <select name="unitaMisura" class="form-select" required>
            <option value="">Seleziona...</option>
            <?php foreach(['kg','g','litro','pezzo'] as $u): ?>
              <option value="<?= $u ?>" <?= ($row['unitaMisura']??'')===$u?'selected':'' ?>><?= $u ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Categoria</label>
          <select name="idCategoria" class="form-select">
            <option value="">— Nessuna —</option>
            <?php foreach($categorie as $c): ?>
              <option value="<?= $c['idCategoria'] ?>" <?= ($row['idCategoria']??'')==$c['idCategoria']?'selected':'' ?>><?= h($c['nomeCategoria']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Descrizione</label>
          <textarea name="descrizione" class="form-control" rows="2" placeholder="Descrizione breve del prodotto..."><?= h($row['descrizione']??'') ?></textarea>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn-ag"><i class="fa-solid fa-floppy-disk"></i> Salva</button>
        <a href="prodotti.php" class="btn-ag-outline"><i class="fa-solid fa-arrow-left"></i> Annulla</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
