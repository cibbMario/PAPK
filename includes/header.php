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
    <title>PAPK TNI AU — Direktori Perwira Matra Udara</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts: Editorial Serif (Playfair Display) + Geometric Sans (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --font-serif: 'Playfair Display', Georgia, 'Times New Roman', serif;
            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --color-dark: #0f172a;
            --color-muted: #64748b;
            --color-border: #e2e8f0;
            --color-bg: #f8fafc;
        }

        body { 
            font-family: var(--font-sans);
            padding-top: 4.5rem; 
            background-color: var(--color-bg); 
            color: var(--color-dark);
            -webkit-font-smoothing: antialiased;
        }

        .font-serif {
            font-family: var(--font-serif);
        }

        .font-sans {
            font-family: var(--font-sans);
        }

        /* Top Navbar Bersih & Minimalis */
        .site-navbar {
            background-color: #ffffff !important;
            border-bottom: 1px solid var(--color-border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .navbar-brand-editorial {
            font-family: var(--font-serif);
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--color-dark) !important;
            letter-spacing: -0.2px;
        }

        .nav-link-minimal {
            font-family: var(--font-sans);
            font-size: 0.88rem;
            font-weight: 500;
            color: #475569 !important;
            padding: 8px 14px !important;
            border-radius: 6px;
            transition: color 0.15s ease;
        }

        .nav-link-minimal:hover {
            color: var(--color-dark) !important;
        }

        .nav-link-minimal.active {
            color: var(--color-dark) !important;
            font-weight: 700;
        }

        .user-tag {
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 4px;
            border: 1px solid var(--color-border);
        }

        .btn-logout-minimal {
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            padding: 5px 12px;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            background: #ffffff;
            transition: all 0.15s ease;
        }

        .btn-logout-minimal:hover {
            color: #0f172a;
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .sidebar { height: calc(100vh - 4.5rem); position: sticky; top: 4.5rem; overflow-y: auto; }
        .list-group-item.active { background-color: #000000; border-color: #000000; color: #ffffff !important; }

        /* Hitam-Putih Global Theme Overrides */
        a { color: var(--color-dark); text-decoration: none; }
        a:hover { color: #000000; }

        .btn-primary, .btn-dark {
            background-color: #0f172a !important;
            border-color: #0f172a !important;
            color: #ffffff !important;
        }
        .btn-primary:hover, .btn-dark:hover {
            background-color: #000000 !important;
            border-color: #000000 !important;
            color: #ffffff !important;
        }
        .btn-outline-primary, .btn-outline-dark {
            background-color: transparent !important;
            border-color: #0f172a !important;
            color: #0f172a !important;
        }
        .btn-outline-primary:hover, .btn-outline-dark:hover {
            background-color: #0f172a !important;
            color: #ffffff !important;
        }
        .btn-success, .btn-info {
            background-color: #1e293b !important;
            border-color: #1e293b !important;
            color: #ffffff !important;
        }
        .btn-warning, .btn-secondary {
            background-color: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }
        .btn-outline-secondary {
            border-color: #cbd5e1 !important;
            color: #475569 !important;
        }
        .btn-outline-secondary:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }

        /* Badges: Monochrome */
        .badge.bg-primary, .badge.bg-dark, .badge.bg-success {
            background-color: #0f172a !important;
            color: #ffffff !important;
            font-weight: 600;
        }
        .badge.bg-secondary, .badge.bg-info, .badge.bg-warning, .badge.bg-light {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1;
            font-weight: 600;
        }
        .badge.bg-danger {
            background-color: transparent !important;
            color: #0f172a !important;
            border: 1px solid #0f172a;
            font-weight: 600;
        }

        /* Alerts: Monochrome */
        .alert {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
            border-radius: 8px;
        }

        /* Form Controls */
        .form-control:focus, .form-select:focus {
            border-color: #0f172a !important;
            box-shadow: none !important;
        }

        /* Pagination */
        .page-link {
            color: #0f172a !important;
            border-color: #e2e8f0 !important;
        }
        .page-item.active .page-link {
            background-color: #0f172a !important;
            border-color: #0f172a !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top site-navbar">
  <div class="container-fluid px-lg-4">
    <a class="navbar-brand navbar-brand-editorial" href="dashboard.php">
      PAPK TNI AU
    </a>

    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarMain">
      <?php if (!empty($_SESSION['username']) && !$isAdmin): ?>
        <!-- Menu Navigasi Atas Khusus User: Bersih, Tanpa Ikon / Emoji -->
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-lg-1">
          <li class="nav-item">
            <a class="nav-link-minimal <?= $currentScript == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
              Direktori Angkatan
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link-minimal <?= $currentScript == 'pengumuman.php' ? 'active' : '' ?>" href="pengumuman.php">
              Pengumuman
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link-minimal <?= $currentScript == 'profil.php' ? 'active' : '' ?>" href="profil.php">
              Profil Saya
            </a>
          </li>
        </ul>
      <?php endif; ?>

      <ul class="navbar-nav ms-auto align-items-center gap-2">
        <?php if (!empty($_SESSION['username'])): ?>
          <?php if (!$isAdmin): ?>
            <li class="nav-item">
              <a class="nav-link-minimal position-relative" href="notifikasi.php">
                Notifikasi
                <?php if ($unreadNotifCount > 0): ?>
                  <span class="badge bg-dark text-white rounded-pill ms-1" style="font-size: 10px;"><?= $unreadNotifCount ?></span>
                <?php endif; ?>
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <span class="user-tag">
              <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
          </li>
          <li class="nav-item">
            <a class="btn-logout-minimal" href="../logout.php">
              Keluar
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<?php if ($isAdmin): ?>
<!-- Layout Admin (Sidebar Khusus Pengelolaan) -->
<div class="container-fluid">
  <div class="row">
    <nav class="col-md-3 col-lg-2 bg-white sidebar py-3 border-end">
      <div class="list-group list-group-flush">
        <small class="text-muted fw-bold px-3 mb-2 text-uppercase" style="font-size: 11px;">Data & Operasional</small>
        <a href="dashboard.php" class="list-group-item list-group-item-action <?= $currentScript == 'dashboard.php' ? 'active' : '';?>">
          Data Anggota & Peta
        </a>
        <a href="talent-pool.php" class="list-group-item list-group-item-action <?= $currentScript == 'talent-pool.php' ? 'active' : '';?>">
          Talent Pool & Kompetensi
        </a>
        <a href="gateway.php" class="list-group-item list-group-item-action <?= $currentScript == 'gateway.php' ? 'active' : '';?>">
          WhatsApp & Gateway
        </a>
        <a href="pengajuan.php" class="list-group-item list-group-item-action <?= $currentScript == 'pengajuan.php' ? 'active' : '';?>">
          Pengajuan Perubahan
        </a>

        <small class="text-muted fw-bold px-3 mt-3 mb-2 text-uppercase" style="font-size: 11px;">Publikasi & Akun</small>
        <a href="pengumuman.php" class="list-group-item list-group-item-action <?= $currentScript == 'pengumuman.php' ? 'active' : '';?>">
          Pengumuman & Berita
        </a>
        <a href="akun.php" class="list-group-item list-group-item-action <?= $currentScript == 'akun.php' ? 'active' : '';?>">
          Verifikasi Akun
        </a>
        <a href="import-export.php" class="list-group-item list-group-item-action <?= $currentScript == 'import-export.php' ? 'active' : '';?>">
          Export/Import Data
        </a>
      </div>
    </nav>
    <main class="col-md-9 col-lg-10 px-md-4 py-3">
<?php else: ?>
<!-- Layout User (Simpel, Terpusat, Editorial & Bersih) -->
<div class="container-xl">
  <div class="row justify-content-center">
    <main class="col-12 py-3">
<?php endif; ?>
