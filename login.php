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
        $error = 'Username dan kata sandi wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            if (isset($user['status']) && $user['status'] === 'pending') {
                $error = 'Akun Anda sedang dalam antrean verifikasi oleh Administrator. Mohon menunggu persetujuan.';
            } elseif (isset($user['status']) && $user['status'] === 'rejected') {
                $error = 'Permohonan pendaftaran akun Anda ditolak oleh Administrator.';
            } elseif (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                $wait = ceil((strtotime($user['locked_until']) - time()) / 60);
                $error = "Akun terkunci sementara karena 5 kali gagal masuk. Coba lagi dalam $wait menit.";
            } else {
                if (password_verify($password, $user['password_hash'])) {
                    $resetStmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id");
                    $resetStmt->execute([':id' => $user['id']]);

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
                    $attempts = (int)$user['failed_attempts'] + 1;
                    $lockUntil = null;
                    if ($attempts >= 5) {
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    }
                    $updStmt = $pdo->prepare("UPDATE users SET failed_attempts = :att, locked_until = :lock WHERE id = :id");
                    $updStmt->execute([':att' => $attempts, ':lock' => $lockUntil, ':id' => $user['id']]);

                    if ($attempts >= 5) {
                        $error = 'Kata sandi salah 5 kali. Akun terkunci selama 15 menit.';
                    } else {
                        $remaining = 5 - $attempts;
                        $error = "Username atau kata sandi tidak cocok. Sisa percobaan: $remaining kali.";
                    }
                }
            }
        } else {
            $error = 'Username atau kata sandi tidak ditemukan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — PAPK TNI AU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts: Editorial Serif (Playfair Display) + Geometric Sans (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --font-serif: 'Playfair Display', Georgia, serif;
            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --color-dark: #0f172a;
            --color-border: #e2e8f0;
        }

        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-sans);
            color: var(--color-dark);
            padding: 24px;
        }

        .auth-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: 14px;
            padding: 36px 32px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
        }

        .brand-heading {
            font-family: var(--font-serif);
            font-size: 1.85rem;
            font-weight: 600;
            letter-spacing: -0.3px;
            color: var(--color-dark);
            margin-bottom: 6px;
            text-align: center;
        }

        .brand-subtitle {
            font-size: 0.88rem;
            color: #64748b;
            text-align: center;
            margin-bottom: 28px;
        }

        .form-label-clean {
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-input-clean {
            font-family: var(--font-sans);
            font-size: 0.92rem;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            transition: border-color 0.15s ease;
            width: 100%;
        }

        .form-input-clean:focus {
            border-color: #0f172a;
            outline: none;
            box-shadow: none;
        }

        .btn-auth {
            font-family: var(--font-sans);
            font-size: 0.9rem;
            font-weight: 600;
            background-color: #0f172a;
            border: 1px solid #0f172a;
            color: #ffffff;
            padding: 11px;
            border-radius: 8px;
            width: 100%;
            transition: background 0.15s ease;
        }

        .btn-auth:hover {
            background-color: #334155;
            border-color: #334155;
            color: #ffffff;
        }

        .auth-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            text-align: center;
            font-size: 0.82rem;
            color: #64748b;
        }

        .auth-link {
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="brand-heading">PAPK TNI AU</div>
    <div class="brand-subtitle">Portal Informasi Perwira Karier</div>

    <?php if (!empty($error)): ?>
        <div class="alert py-2 px-3 small mb-3 text-dark bg-white border border-dark" style="border-radius: 8px;" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label for="username" class="form-label-clean">Username</label>
            <input type="text" class="form-input-clean" id="username" name="username" placeholder="Masukkan username" required autofocus>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label-clean">Kata Sandi</label>
            <input type="password" class="form-input-clean" id="password" name="password" placeholder="Masukkan kata sandi" required>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn-auth">Masuk ke Sistem</button>
        </div>
    </form>

    <div class="auth-footer">
        <div>Belum memiliki akun? <a href="register.php" class="auth-link">Daftar Akun Baru</a></div>
        <div class="mt-2 text-muted" style="font-size: 11px;">
            Akun uji coba: <strong>admin</strong> (admin123) atau <strong>johndoe</strong> (user123)
        </div>
    </div>
</div>

</body>
</html>
