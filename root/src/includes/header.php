<?php
// Header richiede che auth.php sia già stato incluso (che avvia la sessione)
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($pageTitle ?? 'AgroManager') ?> — BavAur-AgroTech</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= $basePath ?? '' ?>assets/css/style.css" rel="stylesheet">
  <!-- Apply saved theme before render to avoid flash -->
  <script>
    (function(){
      var t = localStorage.getItem('ag_theme') || 'light';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
</head>
<body>

<!-- ── SIDEBAR ─────────────────────────────────────────────── -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="brand-icon">🌿</span>
    <div>
      <div class="brand-name">AgroManager</div>
      <div class="brand-sub">BavAur-AgroTech</div>
    </div>
  </div>

  <div class="sidebar-section-label">PRINCIPALE</div>
  <ul class="sidebar-nav">
    <li><a href="<?= $basePath ?>index.php" class="nav-link <?= ($currentPage??'')==='dashboard'?'active':'' ?>">
      <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
  </ul>

  <div class="sidebar-section-label">ANAGRAFICA</div>
  <ul class="sidebar-nav">
    <li><a href="<?= $basePath ?>clienti.php" class="nav-link <?= ($currentPage??'')==='clienti'?'active':'' ?>">
      <i class="fa-solid fa-users"></i><span>Clienti</span></a></li>
    <li><a href="<?= $basePath ?>sedi.php" class="nav-link <?= ($currentPage??'')==='sedi'?'active':'' ?>">
      <i class="fa-solid fa-location-dot"></i><span>Sedi</span></a></li>
    <li><a href="<?= $basePath ?>categorie.php" class="nav-link <?= ($currentPage??'')==='categorie'?'active':'' ?>">
      <i class="fa-solid fa-tags"></i><span>Categorie</span></a></li>
  </ul>

  <div class="sidebar-section-label">PRODOTTI</div>
  <ul class="sidebar-nav">
    <li><a href="<?= $basePath ?>prodotti.php" class="nav-link <?= ($currentPage??'')==='prodotti'?'active':'' ?>">
      <i class="fa-solid fa-seedling"></i><span>Prodotti</span></a></li>
    <li><a href="<?= $basePath ?>produzioni.php" class="nav-link <?= ($currentPage??'')==='produzioni'?'active':'' ?>">
      <i class="fa-solid fa-gears"></i><span>Produzioni</span></a></li>
    <li><a href="<?= $basePath ?>confezioni.php" class="nav-link <?= ($currentPage??'')==='confezioni'?'active':'' ?>">
      <i class="fa-solid fa-box-open"></i><span>Confezioni</span></a></li>
  </ul>

  <div class="sidebar-section-label">VENDITE</div>
  <ul class="sidebar-nav">
    <li><a href="<?= $basePath ?>vendite.php" class="nav-link <?= ($currentPage??'')==='vendite'?'active':'' ?>">
      <i class="fa-solid fa-receipt"></i><span>Vendite</span></a></li>
  </ul>

  <div class="sidebar-section-label">ACCOUNT</div>
  <ul class="sidebar-nav">
    <li><a href="<?= $basePath ?>homepage.php" class="nav-link">
      <i class="fa-solid fa-store"></i><span>Vetrina Pubblica</span></a></li>
    <li><a href="<?= $basePath ?>logout.php" class="nav-link" style="color:rgba(255,100,100,.7)">
      <i class="fa-solid fa-right-from-bracket" style="color:rgba(255,100,100,.5)"></i><span>Logout</span></a></li>
  </ul>

  <div class="sidebar-footer">
    <span>v1.0.0</span>
    <span>©<?= date('Y') ?> BavAur</span>
  </div>
</nav>

<!-- Mobile toggle -->
<button class="sidebar-toggle d-lg-none" id="sidebarToggle">
  <i class="fa-solid fa-bars"></i>
</button>
<div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

<!-- ── MAIN ────────────────────────────────────────────────── -->
<main class="main-content">
  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <h4 class="topbar-title"><?= h($pageTitle ?? 'Dashboard') ?></h4>
      <?php if (!empty($breadcrumb)): ?>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="<?= $basePath ?>index.php">Home</a></li>
          <?php foreach ($breadcrumb as $bc): ?>
            <?php if (isset($bc['url'])): ?>
              <li class="breadcrumb-item"><a href="<?= h($bc['url']) ?>"><?= h($bc['label']) ?></a></li>
            <?php else: ?>
              <li class="breadcrumb-item active"><?= h($bc['label']) ?></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ol>
      </nav>
      <?php endif; ?>
    </div>
    <div class="topbar-right">
      <!-- Theme toggle -->
      <button class="theme-toggle" id="themeToggle" title="Cambia tema">
        <i class="fa-solid fa-moon" id="themeIcon"></i>
      </button>
      <!-- User info -->
      <?php if ($user): ?>
      <div class="user-badge">
        <i class="fa-solid fa-circle-user" style="font-size:1.2rem;color:var(--ag-primary)"></i>
        <div>
          <strong><?= h($user['nome']) ?></strong>
          <?php if ($user['ruolo'] === 'sede_admin' && !empty($user['nomeSede'])): ?>
            <div style="font-size:.7rem;color:var(--ag-text-muted)"><?= h($user['nomeSede']) ?></div>
          <?php endif; ?>
        </div>
        <span class="role-badge role-<?= h($user['ruolo']) ?>">
          <?= match($user['ruolo']) { 'superadmin'=>'Super Admin', 'sede_admin'=>'Sede Admin', default=>'Cliente' } ?>
        </span>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Flash -->
  <?php $flash = getFlash(); if ($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show mx-4 mt-3">
    <i class="fa-solid fa-<?= $flash['type']==='success'?'circle-check':'circle-exclamation' ?> me-2"></i>
    <?= h($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="content-area">
