<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/auth.php'; // auth helper (will be created later)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PAPK Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 4rem; }
        .sidebar { height: 100vh; position: sticky; top: 4rem; }
        .nav-link.active { background-color: #e9ecef; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">PAPK Management</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container-fluid">
  <div class="row">
    <nav class="col-2 bg-light sidebar py-3">
      <div class="list-group">
        <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="dashboard.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '';?>">Dashboard (Data Anggota)</a>
          <a href="pengajuan.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'pengajuan.php' ? 'active' : '';?>">Pengajuan Data</a>
          <a href="akun.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'akun.php' ? 'active' : '';?>">Verifikasi Akun</a>
          <a href="pengumuman.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'pengumuman.php' ? 'active' : '';?>">Pengumuman & Berita</a>
          <a href="import-export.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'import-export.php' ? 'active' : '';?>">Export/Import Excel</a>
          <a href="log.php" class="list-group-item list-group-item-action">Log Aktivitas</a>
        <?php else: ?>
          <a href="dashboard.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '';?>">Dashboard</a>
          <a href="profil.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '';?>">Profil Saya</a>
          <a href="ajukan-perubahan.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'ajukan-perubahan.php' ? 'active' : '';?>">Ajukan Perubahan</a>
          <a href="riwayat-pengajuan.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'riwayat-pengajuan.php' ? 'active' : '';?>">Riwayat Pengajuan</a>
          <a href="pengumuman.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'pengumuman.php' ? 'active' : '';?>">Pengumuman</a>
        <?php endif; ?>
      </div>
    </nav>
    <main class="col-10">
