<?php
// register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/koneksi.php';

// Jika sudah login, redirect
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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passConf = $_POST['password_confirm'] ?? '';
    $regNrp   = trim($_POST['reg_nrp'] ?? '');

    if (empty($fullName) || empty($username) || empty($password) || empty($regNrp)) {
        $error = 'Nama lengkap, NRP, username, dan kata sandi wajib diisi.';
    } elseif ($password !== $passConf) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal harus 6 karakter.';
    } else {
        $stmtChk = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $stmtChk->execute([':u' => $username]);
        if ($stmtChk->fetch()) {
            $error = 'Username sudah digunakan. Silakan pilih username lain.';
        } else {
            $stmtM = $pdo->prepare("SELECT id FROM members WHERE nrp_nia = :nrp LIMIT 1");
            $stmtM->execute([':nrp' => $regNrp]);
            $member = $stmtM->fetch();

            $memberId = null;
            if ($member) {
                $memberId = $member['id'];
            } else {
                $insM = $pdo->prepare("INSERT INTO members (nrp_nia, name, `rank`, korps, unit, status) VALUES (:nrp, :name, 'Letda', 'Sus', 'TNI AU', 'aktif')");
                $insM->execute([
                    ':nrp'  => $regNrp,
                    ':name' => $fullName
                ]);
                $memberId = $pdo->lastInsertId();
            }

            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmtIns = $pdo->prepare("INSERT INTO users (username, password_hash, role, email, status, member_id) 
                                      VALUES (:u, :p, 'user', :email, 'pending', :mid)");
            $stmtIns->execute([
                ':u'     => $username,
                ':p'     => $passwordHash,
                ':email' => $email ?: null,
                ':mid'   => $memberId
            ]);

            $success = 'Pendaftaran berhasil diajukan. Akun Anda sedang menunggu verifikasi oleh Administrator sebelum dapat digunakan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Personel — PAPK TNI AU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts: Editorial Serif + Geometric Sans -->
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
            padding: 32px 20px;
        }

        .auth-card {
            width: 100%;
            max-width: 480px;
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
            margin-bottom: 24px;
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
    <div class="brand-heading">Pendaftaran Personel</div>
    <div class="brand-subtitle">Portal Data Perwira Prajurit Karier TNI AU</div>

    <?php if (!empty($error)): ?>
        <div class="alert py-2 px-3 small mb-3 text-dark bg-white border border-dark" style="border-radius: 8px;" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert py-3 px-3 small mb-3 text-dark bg-light border" style="border-radius: 8px;" role="alert">
            <?= htmlspecialchars($success) ?>
            <div class="mt-3 text-center">
                <a href="login.php" class="auth-link">Kembali ke Halaman Masuk</a>
            </div>
        </div>
    <?php else: ?>
        <form method="POST" action="register.php">
            <div class="mb-3">
                <label class="form-label-clean">Nama Lengkap & Gelar *</label>
                <input type="text" class="form-input-clean" name="full_name" placeholder="Contoh: Letda Sus Arniza, S.H." required>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label-clean">NRP / NIA *</label>
                    <input type="text" class="form-input-clean" name="reg_nrp" placeholder="Contoh: 523455" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-clean">Email</label>
                    <input type="email" class="form-input-clean" name="email" placeholder="nama@email.com">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label-clean">Username *</label>
                <input type="text" class="form-input-clean" name="username" placeholder="Pilih username unik" required>
            </div>

            <div class="row g-2 mb-4">
                <div class="col-md-6">
                    <label class="form-label-clean">Kata Sandi *</label>
                    <input type="password" class="form-input-clean" name="password" placeholder="Minimal 6 karakter" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-clean">Konfirmasi Kata Sandi *</label>
                    <input type="password" class="form-input-clean" name="password_confirm" placeholder="Ulangi kata sandi" required>
                </div>
            </div>

            <button type="submit" class="btn-auth">Ajukan Pendaftaran</button>
        </form>

        <div class="auth-footer">
            Sudah memiliki akun? <a href="login.php" class="auth-link">Masuk ke Sistem</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
