<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/auth.php';

// Ambil jumlah notifikasi belum dibaca jika login
$unreadNotifCount = 0;
if (!empty($_SESSION['user_id'])) {
    $uId = (int)$_SESSION['user_id'];
    try {
        $unreadNotifCount = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = $uId AND is_read = 0")->fetchColumn();
    } catch (Exception $e) {}
}

$isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PAPK TNI AU - Portal Perwira Karier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { padding-top: 4.5rem; background-color: #f8f9fa; }
        .sidebar { height: calc(100vh - 4.5rem); position: sticky; top: 4.5rem; overflow-y: auto; }
        .list-group-item.active { background-color: #1e3c72; border-color: #1e3c72; }
        .navbar-brand { font-weight: 700; letter-spacing: 0.5px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
      <i class="bi bi-shield-shaded me-2 fs-4"></i>
      <span>PAPK TNI AU</span>
    </a>
    
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-auto align-items-center">
        <?php if (!empty($_SESSION['username'])): ?>
          <?php if (!$isAdmin): ?>
            <!-- Lonceng Notifikasi User -->
            <li class="nav-item me-3">
              <a class="nav-link position-relative text-white" href="notifikasi.php" title="Pusat Notifikasi">
                <i class="bi bi-bell-fill fs-5"></i>
                <?php if ($unreadNotifCount > 0): ?>
                  <span class="position-absolute top-1 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">
                    <?= $unreadNotifCount ?>
                  </span>
                <?php endif; ?>
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item me-3">
            <span class="badge bg-light text-dark px-2 py-1">
              <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']) ?> (<?= strtoupper($_SESSION['role'] ?? 'USER') ?>)
            </span>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-light btn-sm" href="../logout.php">
              <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <nav class="col-md-3 col-lg-2 bg-white sidebar py-3 border-end shadow-sm">
      <div class="list-group list-group-flush">
        <?php if ($isAdmin): ?>
          <small class="text-muted fw-bold px-3 mb-2 text-uppercase" style="font-size: 11px;">Data & Operasional</small>
          <a href="dashboard.php" class="list-group-item list-group-item-action <?= $currentScript == 'dashboard.php' ? 'active' : '';?>">
            <i class="bi bi-people me-2"></i> Data Anggota & Peta
          </a>
          <a href="talent-pool.php" class="list-group-item list-group-item-action <?= $currentScript == 'talent-pool.php' ? 'active' : '';?>">
            <i class="bi bi-mortarboard me-2"></i> Talent Pool & Kompetensi
          </a>
          <a href="gateway.php" class="list-group-item list-group-item-action <?= $currentScript == 'gateway.php' ? 'active' : '';?>">
            <i class="bi bi-whatsapp me-2 text-success"></i> WhatsApp & Gateway
          </a>
          <a href="pengajuan.php" class="list-group-item list-group-item-action <?= $currentScript == 'pengajuan.php' ? 'active' : '';?>">
            <i class="bi bi-check-circle me-2"></i> Pengajuan Perubahan
          </a>

          <small class="text-muted fw-bold px-3 mt-3 mb-2 text-uppercase" style="font-size: 11px;">Publikasi & Akun</small>
          <a href="pengumuman.php" class="list-group-item list-group-item-action <?= $currentScript == 'pengumuman.php' ? 'active' : '';?>">
            <i class="bi bi-megaphone me-2"></i> Pengumuman & Berita
          </a>
          <a href="akun.php" class="list-group-item list-group-item-action <?= $currentScript == 'akun.php' ? 'active' : '';?>">
            <i class="bi bi-person-check me-2"></i> Verifikasi Akun
          </a>
          <a href="import-export.php" class="list-group-item list-group-item-action <?= $currentScript == 'import-export.php' ? 'active' : '';?>">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i> Export/Import Data
          </a>
        <?php else: ?>
          <small class="text-muted fw-bold px-3 mb-2 text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Portal Personel</small>
          <a href="dashboard.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $currentScript == 'dashboard.php' ? 'active' : '';?>">
            <div><i class="bi bi-people-fill me-2"></i> Direktori Angkatan</div>
            <span class="badge bg-light text-primary border rounded-pill" style="font-size: 10px;">92</span>
          </a>
          <a href="profil.php" class="list-group-item list-group-item-action <?= $currentScript == 'profil.php' ? 'active' : '';?>">
            <i class="bi bi-person-circle me-2"></i> Profil & Data Saya
          </a>
          <a href="kartu-anggota.php" class="list-group-item list-group-item-action <?= $currentScript == 'kartu-anggota.php' ? 'active' : '';?>">
            <i class="bi bi-credit-card-2-front me-2"></i> KTA Digital
          </a>
          <a href="pengumuman.php" class="list-group-item list-group-item-action <?= $currentScript == 'pengumuman.php' ? 'active' : '';?>">
            <i class="bi bi-megaphone me-2"></i> Pengumuman Dinas
          </a>
        <?php endif; ?>
      </div>
    </nav>
    <main class="col-md-9 col-lg-10 px-md-4 py-3">
