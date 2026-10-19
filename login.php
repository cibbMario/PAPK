<?php
// login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/koneksi.php';

// Jika sudah login, redirect sesuai role
if (!empty($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } else {
        header('Location: user/dashboard.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        // Cari user di database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            // Cek status verifikasi akun oleh admin
            if (isset($user['status']) && $user['status'] === 'pending') {
                $error = 'Akun Anda sedang dalam antrean verifikasi oleh Administrator. Mohon menunggu persetujuan!';
            } elseif (isset($user['status']) && $user['status'] === 'rejected') {
                $error = 'Permohonan pendaftaran akun Anda ditolak oleh Administrator. Silakan hubungi admin!';
            } elseif (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                // Cek apakah akun sedang terkunci (brute force protection)
                $wait = ceil((strtotime($user['locked_until']) - time()) / 60);
                $error = "Akun terkunci sementara karena 5x gagal login. Coba lagi dalam $wait menit.";
            } else {
                // Verifikasi password
                if (password_verify($password, $user['password_hash'])) {
                    // Reset failed_attempts jika sukses
                    $resetStmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id");
                    $resetStmt->execute([':id' => $user['id']]);

                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: user/dashboard.php');
                    }
                    exit;
                } else {
                    // Password salah -> tambah failed_attempts
                    $attempts = (int)$user['failed_attempts'] + 1;
                    $lockUntil = null;
                    if ($attempts >= 5) {
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    }
                    $updStmt = $pdo->prepare("UPDATE users SET failed_attempts = :att, locked_until = :lock WHERE id = :id");
                    $updStmt->execute([':att' => $attempts, ':lock' => $lockUntil, ':id' => $user['id']]);

                    if ($attempts >= 5) {
                        $error = 'Password salah 5 kali! Akun terkunci selama 15 menit.';
                    } else {
                        $remaining = 5 - $attempts;
                        $error = "Username atau password salah! Sisa percobaan: $remaining kali.";
                    }
                }
            }
        } else {
            $error = 'Username atau password tidak ditemukan!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PAPK TNI AU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            border: none;
        }
        .card-header {
            background-color: transparent;
            border-bottom: none;
            text-align: center;
            padding-top: 2rem;
        }
        .brand-title {
            font-weight: 700;
            color: #1e3c72;
            letter-spacing: 1px;
        }
        .btn-primary {
            background-color: #1e3c72;
            border-color: #1e3c72;
            padding: 10px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #15294f;
            border-color: #15294f;
        }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="card login-card mx-auto bg-white">
        <div class="card-header">
            <h3 class="brand-title mb-1">PAPK TNI AU</h3>
            <p class="text-muted small">Sistem Informasi Manajemen Data Anggota</p>
        </div>
        <div class="card-body px-4 pb-4">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="username" class="form-label small fw-semibold">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">Masuk ke Sistem</button>
                </div>
            </form>
            <div class="text-center mt-3 pt-2 border-top">
                <span class="small text-muted">Belum memiliki akun perwira?</span><br>
                <a href="register.php" class="text-primary fw-semibold small text-decoration-none">Daftar Akun Baru (Verifikasi Admin)</a>
            </div>
            <div class="text-center mt-2">
                <small class="text-muted" style="font-size: 11px;">Akun demo: <strong>admin</strong> (admin123) | <strong>johndoe</strong> (user123)</small>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
