<?php
require_once 'includes/auth.php';
$currentPage = 'vendite';
$basePath    = '';
$action    = $_GET['action'] ?? 'list';
$id        = isset($_GET['id'])      ? (int)$_GET['id']      : null;
$cliFilter = isset($_GET['cliente']) ? (int)$_GET['cliente'] : null;
$mySedeId  = userSede();

// ── PREPARED STATEMENTS ──────────────────────────────────────
$stmtGetDets      = $pdo->prepare(
    "SELECT idConfezione, quantita
     FROM DETTAGLIO_VENDITA
     WHERE idVendita=? AND idConfezione IS NOT NULL"
);
$stmtRestoreGiac  = $pdo->prepare(
    "UPDATE CONFEZIONE SET giacenza = giacenza + ? WHERE idConfezione=?"
);
$stmtDecGiac      = $pdo->prepare(
    "UPDATE CONFEZIONE SET giacenza = GREATEST(0, giacenza - ?) WHERE idConfezione=?"
);
$stmtDelDets      = $pdo->prepare(
    "DELETE FROM DETTAGLIO_VENDITA WHERE idVendita=?"
);
$stmtDelVendita   = $pdo->prepare(
    "DELETE FROM VENDITA WHERE idVendita=?"
);
$stmtInsVendita   = $pdo->prepare(
    "INSERT INTO VENDITA (dataVendita, totalePagato, note, idCliente, idSede)
     VALUES (?, ?, ?, ?, ?)"
);
$stmtUpdVendita   = $pdo->prepare(
    "UPDATE VENDITA
     SET dataVendita=?, totalePagato=?, note=?, idCliente=?, idSede=?
     WHERE idVendita=?"
);
$stmtInsDettaglio = $pdo->prepare(
    "INSERT INTO DETTAGLIO_VENDITA
         (quantita, prezzoUnitario, omaggio, idVendita, idProdotto, idConfezione)
     VALUES (?, ?, ?, ?, ?, ?)"
);

// ── DELETE ───────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    $pdo->beginTransaction();
    try {
        // 1. Ripristina giacenze
        $stmtGetDets->execute([$id]);
        foreach ($stmtGetDets->fetchAll() as $d) {
            $stmtRestoreGiac->execute([$d['quantita'], $d['idConfezione']]);
        }
        // 2. Elimina vendita (CASCADE elimina dettagli)
        $stmtDelVendita->execute([$id]);
        $pdo->commit();
        flash('success', 'Vendita eliminata e giacenze ripristinate.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
    }
    redirect('vendite.php');
}

// ── POST: create / edit ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create', 'edit'])) {
    $dataVend  = $_POST['dataVendita']  ?? '';
    $totPagato = (float)($_POST['totalePagato'] ?? 0);
    $note      = trim($_POST['note'] ?? '') ?: null;
    $idCli     = (int)($_POST['idCliente'] ?? 0);
    $idSede    = $mySedeId ?? (int)($_POST['idSede'] ?? 0);
    $prods     = $_POST['det_prodotto']   ?? [];
    $confs     = $_POST['det_confezione'] ?? [];
    $qtys      = $_POST['det_quantita']   ?? [];
    $prices    = $_POST['det_prezzo']     ?? [];
    $omaggi    = $_POST['det_omaggio']    ?? [];

    if (!$dataVend || !$idCli || !$idSede || empty(array_filter($prods))) {
        flash('error', 'Compila tutti i campi obbligatori e aggiungi almeno un prodotto.');
        redirect("vendite.php?action=$action" . ($id ? "&id=$id" : ''));
    }

    $pdo->beginTransaction();
    try {
        // Costruisci le righe
        $righe = [];
        foreach ($prods as $i => $pid) {
            if (!$pid) continue;
            $isOmag  = isset($omaggi[$i]) ? 1 : 0;
            $prezzo  = $isOmag ? 0.0 : (float)($prices[$i] ?? 0);
            $qty     = (float)($qtys[$i] ?? 1);
            $conf    = !empty($confs[$i]) ? (int)$confs[$i] : null;

            // Vincolo 3NF applicativo (MySQL #3823 impedisce CHECK su colonne FK):
            // lavorato → confezione presente, idProdotto NULL (derivabile)
            // fresco   → confezione assente,  idProdotto NOT NULL
            if ($conf !== null && (int)$pid === 0) {
                throw new \InvalidArgumentException("Riga $i: prodotto mancante per articolo lavorato.");
            }

            $righe[] = [
                'pid'    => (int)$pid,
                'qty'    => $qty,
                'prezzo' => $prezzo,
                'omag'   => $isOmag,
                'conf'   => $conf,
            ];
        }

        if ($action === 'edit') {
            // 1. Ripristina giacenze vecchie, riusa stmtRestoreGiac
            $stmtGetDets->execute([$id]);
            foreach ($stmtGetDets->fetchAll() as $o) {
                $stmtRestoreGiac->execute([$o['quantita'], $o['idConfezione']]);
            }
            // 2. Elimina dettagli vecchi
            $stmtDelDets->execute([$id]);
            // 3. Aggiorna testata
            $stmtUpdVendita->execute([$dataVend, $totPagato, $note, $idCli, $idSede, $id]);
            $vid = $id;
        } else {
            // Inserisce nuova vendita
            $stmtInsVendita->execute([$dataVend, $totPagato, $note, $idCli, $idSede]);
            $vid = $pdo->lastInsertId();
        }

        // 4. Inserisce i nuovi dettagli e scala le giacenze (riusa gli stmt)
        foreach ($righe as $r) {
            // 3NF: lavorati hanno confezione → idProdotto NULL (derivabile)
            //      freschi non hanno confezione → idProdotto NOT NULL
            $idProdSave = $r['conf'] ? null : $r['pid'];
            $stmtInsDettaglio->execute([
                $r['qty'], $r['prezzo'], $r['omag'],
                $vid, $idProdSave, $r['conf']
            ]);
            if ($r['conf'] && !$r['omag']) {
                $stmtDecGiac->execute([$r['qty'], $r['conf']]);
            }
        }

        $pdo->commit();
        flash('success', "Vendita #$vid salvata.");
        redirect("vendite.php?action=view&id=$vid");

    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'Errore: ' . $e->getMessage());
        redirect("vendite.php?action=$action" . ($id ? "&id=$id" : ''));
    }
}

// ── FETCH VIEW / EDIT ─────────────────────────────────────────
$vendita = null;
$dettagli = [];
if ($action === 'view' && $id) {
    $stmtV = $pdo->prepare("
        SELECT v.*, cl.nome AS cliente, cl.nickname, s.nomeSede
        FROM V_VENDITA v
        JOIN CLIENTE cl ON v.idCliente = cl.idCliente
        JOIN SEDE    s  ON v.idSede    = s.idSede
        WHERE v.idVendita=?
    ");
    $stmtV->execute([$id]);
    $vendita = $stmtV->fetch();
    if (!$vendita) { flash('error', 'Non trovata.'); redirect('vendite.php'); }

    $stmtD = $pdo->prepare("SELECT * FROM V_DETTAGLIO WHERE idVendita=?");
    $stmtD->execute([$id]);
    $dettagli = $stmtD->fetchAll();
}

$row = null;
if ($action === 'edit' && $id) {
    $stmtR = $pdo->prepare("SELECT * FROM VENDITA WHERE idVendita=?");
    $stmtR->execute([$id]);
    $row = $stmtR->fetch();
    if (!$row) { flash('error', 'Non trovata.'); redirect('vendite.php'); }

    $stmtD2 = $pdo->prepare("SELECT * FROM DETTAGLIO_VENDITA WHERE idVendita=?");
    $stmtD2->execute([$id]);
    $dettagli = $stmtD2->fetchAll();
}

// ── LOOKUP DATA ───────────────────────────────────────────────
$stmtCli  = $pdo->prepare("SELECT * FROM CLIENTE ORDER BY nome");
$stmtCli->execute();
$clienti  = $stmtCli->fetchAll();

$stmtSedi = $pdo->prepare("SELECT * FROM SEDE ORDER BY nomeSede");
$stmtSedi->execute();
$sedi     = $stmtSedi->fetchAll();

$stmtProd = $pdo->prepare("SELECT * FROM PRODOTTO ORDER BY nome");
$stmtProd->execute();
$prodotti = $stmtProd->fetchAll();

$stmtConf = $pdo->prepare("
    SELECT c.*, pp.nome AS prodotto, pp.unitaMisura
    FROM CONFEZIONE c
    JOIN PRODUZIONE pr ON c.idProduzione         = pr.idProduzione
    JOIN PRODOTTO   pp ON pr.idProdottoProdotto  = pp.idProdotto
    WHERE c.giacenza > 0
    ORDER BY pp.nome
");
$stmtConf->execute();
$confezioni = $stmtConf->fetchAll();

// ── LIST ──────────────────────────────────────────────────────
$wheresL = [];
$paramsL = [];
if ($cliFilter) { $wheresL[] = "v.idCliente=?"; $paramsL[] = $cliFilter; }
if ($mySedeId)  { $wheresL[] = "v.idSede=$mySedeId"; }
$wL = $wheresL ? "WHERE " . implode(' AND ', $wheresL) : '';

$stmtList = $pdo->prepare("
    SELECT v.*, cl.nome AS cliente, s.nomeSede,
           COUNT(d.idDettaglio) AS nRighe
    FROM V_VENDITA v
    JOIN CLIENTE cl ON v.idCliente = cl.idCliente
    JOIN SEDE    s  ON v.idSede    = s.idSede
    LEFT JOIN DETTAGLIO_VENDITA d ON d.idVendita = v.idVendita
    $wL
    GROUP BY v.idVendita
    ORDER BY v.dataVendita DESC, v.idVendita DESC
");
$stmtList->execute($paramsL);
$vendite = $stmtList->fetchAll();
$pageTitle  = match($action){'create'=>'Nuova Vendita','edit'=>'Modifica Vendita','view'=>'Dettaglio Vendita',default=>'Vendite'};
$breadcrumb = $action!=='list'?[['label'=>'Vendite','url'=>'vendite.php'],['label'=>$pageTitle]]:null;
require_once 'includes/header.php';
?>

<?php if ($action==='list'): ?>
<div class="page-header">
  <h5 class="page-header-title"><i class="fa-solid fa-receipt"></i> Vendite (<?= count($vendite) ?>)</h5>
  <a href="?action=create" class="btn-ag"><i class="fa-solid fa-plus"></i> Nuova Vendita</a>
</div>
<div class="ag-card">
  <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-list"></i> Registro Vendite</h6></div>
  <div class="table-responsive">
    <?php if(empty($vendite)): ?>
      <div class="empty-state"><i class="fa-solid fa-receipt"></i><p>Nessuna vendita registrata.</p></div>
    <?php else: ?>
    <table class="ag-table table mb-0">
      <thead><tr><th>#</th><th>Data</th><th>Cliente</th><th>Sede</th><th>Righe</th><th>Calcolato</th><th>Pagato</th><th class="text-end">Azioni</th></tr></thead>
      <tbody>
      <?php foreach($vendite as $v): $diff=$v['totaleCalcolato']-$v['totalePagato']; ?>
      <tr>
        <td class="fw-semibold text-muted">#<?= $v['idVendita'] ?></td>
        <td><?= date('d/m/Y',strtotime($v['dataVendita'])) ?></td>
        <td class="fw-semibold"><?= h($v['cliente']) ?></td>
        <td><small class="text-muted"><?= h($v['nomeSede']) ?></small></td>
        <td><span class="badge-ok"><?= $v['nRighe'] ?></span></td>
        <td>€<?= number_format($v['totaleCalcolato'],2,',','.') ?></td>
        <td>
          <span class="fw-bold vendita-pagato" style="color:<?= $diff>0?'var(--ag-warning-text)':'var(--ag-primary)' ?>">€<?= number_format($v['totalePagato'],2,',','.') ?></span>
          <?php if($diff>0): ?><small class="text-warning d-block" style="font-size:.7rem">-€<?= number_format($diff,2,',','.') ?></small><?php endif; ?>
        </td>
        <td class="text-end">
          <a href="?action=view&id=<?= $v['idVendita'] ?>" class="btn-view-sm me-1"><i class="fa-solid fa-eye"></i></a>
          <a href="?action=edit&id=<?= $v['idVendita'] ?>" class="btn-edit-sm me-1"><i class="fa-solid fa-pen"></i></a>
          <a href="?action=delete&id=<?= $v['idVendita'] ?>" class="btn-danger-sm" onclick="return confirm('Eliminare vendita #<?= $v['idVendita'] ?>?')"><i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($action==='view' && $vendita): ?>
<div class="page-header">
  <h5 class="page-header-title"><i class="fa-solid fa-receipt"></i> Vendita #<?= $vendita['idVendita'] ?></h5>
  <div class="d-flex gap-2">
    <a href="?action=edit&id=<?= $vendita['idVendita'] ?>" class="btn-ag-outline"><i class="fa-solid fa-pen"></i> Modifica</a>
    <a href="vendite.php" class="btn-ag-outline"><i class="fa-solid fa-arrow-left"></i> Elenco</a>
  </div>
</div>
<div class="ag-card mb-4">
  <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-circle-info"></i> Informazioni</h6></div>
  <div class="vendita-meta">
    <div><div class="meta-label">Data</div><div class="meta-value"><?= date('d/m/Y',strtotime($vendita['dataVendita'])) ?></div></div>
    <div><div class="meta-label">Cliente</div><div class="meta-value"><?= h($vendita['cliente']) ?><?= $vendita['nickname']?' <small class="text-muted">('.h($vendita['nickname']).')</small>':'' ?></div></div>
    <div><div class="meta-label">Sede</div><div class="meta-value"><?= h($vendita['nomeSede']) ?></div></div>
    <div><div class="meta-label">Note</div><div class="meta-value"><?= $vendita['note']?h($vendita['note']):'<span class="text-muted">—</span>' ?></div></div>
  </div>
</div>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="ag-card">
      <div class="ag-card-header"><h6 class="ag-card-title"><i class="fa-solid fa-list"></i> Righe Vendita</h6></div>
      <div class="table-responsive">
        <table class="ag-table table mb-0">
          <thead><tr><th>Prodotto</th><th>Confezione</th><th>Qta</th><th>Prezzo</th><th>Subtotale</th><th>Tipo</th></tr></thead>
          <tbody>
          <?php foreach($dettagli as $d): $sub=$d['omaggio']?0:$d['quantita']*$d['prezzoUnitario']; ?>
          <tr>
            <td class="fw-semibold"><?= h($d['prodotto']) ?></td>
            <td><?= $d['idConfezione']?'<small class="text-muted">'.h($d['pesoNetto']).' '.$d['unitaMisura'].'</small>':'<small class="text-muted">fresco</small>' ?></td>
            <td><?= h($d['quantita']) ?></td>
            <td><?= $d['omaggio']?'—':'€'.number_format($d['prezzoUnitario'],2,',','.') ?></td>
            <td class="fw-semibold"><?= $d['omaggio']?'—':'€'.number_format($sub,2,',','.') ?></td>
            <td><?= $d['omaggio']?'<span class="badge-omaggio">Omaggio</span>':'<span class="badge-ok">Normale</span>' ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4 d-flex flex-column gap-3">
    <div class="total-box">
      <div class="label">Totale Calcolato</div>
      <div class="value">€<?= number_format($vendita['totaleCalcolato'],2,',','.') ?></div>
    </div>
    <div class="total-box" style="background:linear-gradient(135deg,#065f46,#047857)">
      <div class="label">Totale Pagato</div>
      <div class="value">€<?= number_format($vendita['totalePagato'],2,',','.') ?></div>
      <?php $diff=$vendita['totaleCalcolato']-$vendita['totalePagato']; if($diff>0): ?>
        <div class="label mt-2" style="color:#fde68a">Saldo: €<?= number_format($diff,2,',','.') ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php else: // CREATE / EDIT form ?>
<div class="ag-form-card" style="max-width:100%">
  <div class="ag-form-header">
    <h5><i class="fa-solid fa-<?= $action==='create'?'plus':'pen' ?> me-2"></i><?= h($pageTitle) ?></h5>
    <p>Registra una transazione commerciale con i prodotti venduti.</p>
  </div>
  <div class="ag-form-body">
    <form method="POST" action="?action=<?= h($action) ?><?= $id?"&id=$id":'' ?>" id="venditaForm">
      <div class="row g-3 mb-4 pb-4" style="border-bottom:1px solid var(--ag-border)">
        <div class="col-md-3">
          <label class="form-label">Data <span class="text-danger">*</span></label>
          <input type="date" name="dataVendita" class="form-control" required value="<?= h($row['dataVendita']??date('Y-m-d')) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Cliente <span class="text-danger">*</span></label>
          <select name="idCliente" class="form-select" required>
            <option value="">Seleziona cliente...</option>
            <?php foreach($clienti as $c): ?>
              <option value="<?= $c['idCliente'] ?>" <?= ($row['idCliente']??'')==$c['idCliente']?'selected':'' ?>>
                <?= h($c['nome']) ?><?= $c['nickname']?' ('.h($c['nickname']).')':'' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Sede <span class="text-danger">*</span></label>
          <?php if ($mySedeId): ?>
            <?php $ss=array_filter($sedi,fn($x)=>$x['idSede']===$mySedeId); $ss=reset($ss); ?>
            <input type="text" class="form-control" value="<?= h($ss['nomeSede']??'') ?>" readonly>
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
        <div class="col-md-3">
          <label class="form-label">Totale Pagato (€) <span class="text-danger">*</span></label>
          <input type="number" name="totalePagato" id="totalePagato" class="form-control" step="0.01" min="0" required value="<?= h($row['totalePagato']??'0.00') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Note</label>
          <textarea name="note" class="form-control" rows="2" placeholder="Note opzionali..."><?= h($row['note']??'') ?></textarea>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0 fw-bold" style="color:var(--ag-text)"><i class="fa-solid fa-list me-2" style="color:var(--ag-primary)"></i>Prodotti Venduti</h6>
        <button type="button" class="btn-ag btn-ag-sm" id="addRiga"><i class="fa-solid fa-plus"></i> Aggiungi riga</button>
      </div>

      <div id="righeContainer">
        <?php $righeEdit = !empty($dettagli)?$dettagli:[[]]; foreach($righeEdit as $idx=>$det): ?>
        <div class="det-row-form" data-idx="<?= $idx ?>">
          <button type="button" class="det-remove-btn" onclick="removeRiga(this)"><i class="fa-solid fa-xmark"></i></button>
          <div class="row g-2 align-items-end">
            <div class="col-md-4">
              <label class="form-label" style="font-size:.78rem">Prodotto <span class="text-danger">*</span></label>
              <select name="det_prodotto[]" class="form-select form-select-sm">
                <option value="">Seleziona...</option>
                <?php foreach($prodotti as $p): ?>
                  <option value="<?= $p['idProdotto'] ?>" <?= ($det['idProdotto']??'')==$p['idProdotto']?'selected':'' ?>>
                    <?= h($p['nome']) ?> (<?= h($p['tipoProdotto']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label" style="font-size:.78rem">Confezione</label>
              <select name="det_confezione[]" class="form-select form-select-sm">
                <option value="">— Fresco / Nessuna —</option>
                <?php foreach($confezioni as $cf): ?>
                  <option value="<?= $cf['idConfezione'] ?>" <?= ($det['idConfezione']??'')==$cf['idConfezione']?'selected':'' ?>>
                    #<?= $cf['idConfezione'] ?> — <?= h($cf['prodotto']) ?> <?= $cf['pesoNetto'] ?>kg (<?= $cf['giacenza'] ?> disp.)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label" style="font-size:.78rem">Quantità <span class="text-danger">*</span></label>
              <input type="number" name="det_quantita[]" class="form-control form-control-sm qty-input" step="0.01" min="0.01" required value="<?= h($det['quantita']??1) ?>" onchange="calcolaTotale()">
            </div>
            <div class="col-md-2">
              <label class="form-label" style="font-size:.78rem">Prezzo Unit. (€)</label>
              <input type="number" name="det_prezzo[]" class="form-control form-control-sm price-input" step="0.01" min="0" value="<?= h($det['prezzoUnitario']??'0.00') ?>" onchange="calcolaTotale()">
            </div>
            <div class="col-md-1">
              <label class="form-label" style="font-size:.78rem">🎁 Omaggio</label>
              <div class="form-check mt-1">
                <input type="checkbox" name="det_omaggio[<?= $idx ?>]" class="form-check-input omaggio-check"
                       id="omag<?= $idx ?>" onchange="toggleOmaggio(this)" <?= !empty($det['omaggio'])?'checked':'' ?>>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="d-flex align-items-center justify-content-end gap-3 mt-3 p-3" style="background:var(--ag-very-pale);border-radius:12px;border:1px solid var(--ag-border)">
        <span style="font-size:.9rem;color:var(--ag-text-muted)">Totale calcolato:</span>
        <span id="totaleDisplay" style="font-size:1.4rem;font-weight:700;color:var(--ag-text)">€0,00</span>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn-ag"><i class="fa-solid fa-floppy-disk"></i> Salva Vendita</button>
        <a href="vendite.php" class="btn-ag-outline"><i class="fa-solid fa-arrow-left"></i> Annulla</a>
      </div>
    </form>
  </div>
</div>

<?php $extraJs = '<script>
let rigaIdx = '.( count($righeEdit??[[]])-1 ).';

function removeRiga(btn) {
  const c = document.getElementById("righeContainer");
  if (c.querySelectorAll(".det-row-form").length <= 1) return;
  btn.closest(".det-row-form").remove(); calcolaTotale();
}

document.getElementById("addRiga").addEventListener("click", () => {
  rigaIdx++;
  const c = document.getElementById("righeContainer");
  const tpl = c.querySelector(".det-row-form").cloneNode(true);
  tpl.setAttribute("data-idx", rigaIdx);
  tpl.querySelectorAll("select,input[type=number]").forEach(el => {
    if (el.type==="number") el.value = el.name.includes("quantita")?"1":"0.00";
    else el.selectedIndex = 0;
  });
  const cb = tpl.querySelector(".omaggio-check");
  cb.checked = false; cb.name="det_omaggio["+rigaIdx+"]"; cb.id="omag"+rigaIdx;
  cb.closest(".det-row-form").querySelector("label[for]").setAttribute("for","omag"+rigaIdx);
  tpl.querySelector(".price-input").disabled = false;
  c.appendChild(tpl); calcolaTotale();
});

function toggleOmaggio(cb) {
  const pi = cb.closest(".det-row-form").querySelector(".price-input");
  if (cb.checked) { pi.value="0.00"; pi.disabled=true; } else { pi.disabled=false; }
  calcolaTotale();
}

function calcolaTotale() {
  let tot=0;
  document.querySelectorAll(".det-row-form").forEach(r=>{
    if (r.querySelector(".omaggio-check").checked) return;
    const q=parseFloat(r.querySelector(".qty-input").value)||0;
    const p=parseFloat(r.querySelector(".price-input").value)||0;
    tot+=q*p;
  });
  document.getElementById("totaleDisplay").textContent="€"+tot.toFixed(2).replace(".",",");
  const tp=document.getElementById("totalePagato");
  if (!tp.dataset.touched) tp.value=tot.toFixed(2);
}

document.getElementById("totalePagato").addEventListener("input",function(){this.dataset.touched="1";});
document.addEventListener("DOMContentLoaded",()=>{
  calcolaTotale();
  document.querySelectorAll(".omaggio-check").forEach(cb=>{ if(cb.checked) cb.closest(".det-row-form").querySelector(".price-input").disabled=true; });
});
</script>'; ?>

<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
