<?php
// register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/koneksi.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $nrp      = trim($_POST['reg_nrp'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($fullName) || empty($nrp) || empty($username) || empty($password)) {
        $error = 'Semua kolom bertanda bintang (*) wajib diisi!';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter demi keamanan akun!';
    } else {
        // Cek apakah username atau email sudah ada
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :uname OR email = :email LIMIT 1");
        $check->execute([':uname' => $username, ':email' => $email]);
        if ($check->fetch()) {
            $error = 'Username atau Email sudah pernah didaftarkan. Gunakan yang lain!';
        } else {
            // Cek apakah ada anggota di tabel members yang cocok dengan NRP ini untuk otomatis ditautkan nanti
            $stmtMember = $pdo->prepare("SELECT id FROM members WHERE nrp_nia = :nrp LIMIT 1");
            $stmtMember->execute([':nrp' => $nrp]);
            $foundMemberId = $stmtMember->fetchColumn() ?: null;

            // Simpan akun dengan status 'pending' (perlu verifikasi admin)
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("INSERT INTO users (username, full_name, reg_nrp, email, password_hash, role, status, member_id) 
                                  VALUES (:uname, :fname, :nrp, :email, :pass, 'user', 'pending', :mid)");
            $ins->execute([
                ':uname' => $username,
                ':fname' => $fullName,
                ':nrp'   => $nrp,
                ':email' => $email ?: null,
                ':pass'  => $passwordHash,
                ':mid'   => $foundMemberId
            ]);

            $success = 'Pendaftaran akun Anda BERHASIL diajukan! Demi keamanan, akun berstatus <strong>Pending</strong> dan akan diverifikasi oleh Administrator sebelum Anda dapat login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun Anggota - PAPK TNI AU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .register-card {
            width: 100%;
            max-width: 520px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: none;
        }
        .brand-title {
            font-weight: 700;
            color: #1e3c72;
        }
        .btn-primary {
            background-color: #1e3c72;
            border-color: #1e3c72;
            padding: 10px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #15294f;
        }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="card register-card mx-auto bg-white">
        <div class="card-header bg-transparent text-center border-0 pt-4 pb-0">
            <h3 class="brand-title mb-1">Pendaftaran Personel PAPK</h3>
            <p class="text-muted small">Portal Sistem Data Anggota Perwira Prajurit Karier TNI AU</p>
        </div>
        <div class="card-body px-4 pb-4">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success py-3 small" role="alert">
                    <?= $success ?>
                    <div class="mt-3 text-center">
                        <a href="login.php" class="btn btn-sm btn-outline-success fw-semibold">Kembali ke Halaman Login</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info border-0 py-2 small mb-3">
                    <i class="bi bi-shield-lock"></i> <strong>Keamanan Terjaga:</strong> Seluruh permohonan akun baru harus melalui proses validasi NRP dan persetujuan Administrator.
                </div>

                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap & Gelar *</label>
                        <input type="text" class="form-control" name="full_name" placeholder="Contoh: Lettu Lek Rizky Pratama, S.T." required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">NRP / NIA Perwira *</label>
                            <input type="text" class="form-control" name="reg_nrp" placeholder="Contoh: 523881" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Email Militer / Pribadi</label>
                            <input type="email" class="form-control" name="email" placeholder="nama@tni-au.mil.id">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Username *</label>
                        <input type="text" class="form-control" name="username" placeholder="Buat username untuk login" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Password *</label>
                            <input type="password" class="form-control" name="password" placeholder="Minimal 6 karakter" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Ulangi Password *</label>
                            <input type="password" class="form-control" name="confirm_password" placeholder="Konfirmasi password" required>
                        </div>
                    </div>

                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary">Ajukan Akun Baru</button>
                    </div>
                </form>

                <div class="text-center mt-3 small">
                    Sudah memiliki akun? <a href="login.php" class="text-decoration-none fw-semibold">Masuk di sini</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
