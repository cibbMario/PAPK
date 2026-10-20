<?php
// admin/akun.php
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

// Handle Persetujuan / Penolakan / Hapus Akun
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        if ($action === 'approve') {
            $upd = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = :id");
            $upd->execute([':id' => $userId]);
            setFlash('Akun berhasil DISETUJUI! Personel kini dapat login ke sistem.', 'success');
        } elseif ($action === 'reject') {
            $upd = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = :id");
            $upd->execute([':id' => $userId]);
            setFlash('Permohonan akun telah DITOLAK.', 'danger');
        } elseif ($action === 'reset_password') {
            $newHash = password_hash('papk123', PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE users SET password_hash = :hash, failed_attempts = 0, locked_until = NULL WHERE id = :id");
            $upd->execute([':hash' => $newHash, ':id' => $userId]);
            setFlash('Password akun berhasil di-reset menjadi: papk123', 'info');
        }
    }
    header('Location: akun.php');
    exit;
}

// Ambil data users
$filter = $_GET['status'] ?? 'all';
$sql = "SELECT u.*, m.name as linked_name, m.rank as linked_rank 
        FROM users u 
        LEFT JOIN members m ON u.member_id = m.id ";
if ($filter === 'pending') {
    $sql .= "WHERE u.status = 'pending' ";
} elseif ($filter === 'approved') {
    $sql .= "WHERE u.status = 'approved' ";
}
$sql .= "ORDER BY (u.status = 'pending') DESC, u.id DESC";
$users = $pdo->query($sql)->fetchAll();

// Hitung antrean pending
$pendingTotal = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 font-serif fw-bold mb-1">Verifikasi & Kelola Akun Pengguna</h1>
        <p class="text-muted small mb-0">Kelola akun personel dan tinjau pendaftaran akun baru sebelum diberikan hak akses.</p>
    </div>
    <div class="btn-group">
        <a href="akun.php?status=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-dark' : 'btn-outline-secondary' ?>">Semua Akun</a>
        <a href="akun.php?status=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-dark' : 'btn-outline-secondary' ?>">
            Antrean Pending (<?= $pendingTotal ?>)
        </a>
        <a href="akun.php?status=approved" class="btn btn-sm <?= $filter === 'approved' ? 'btn-dark' : 'btn-outline-secondary' ?>">Aktif Disetujui</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Pengguna</th>
                        <th>NRP Diajukan</th>
                        <th>Email</th>
                        <th>Terkait Anggota</th>
                        <th>Role</th>
                        <th>Status Akun</th>
                        <th class="pe-3 text-center">Tindakan Admin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $u): ?>
                            <tr class="<?= $u['status'] === 'pending' ? 'table-warning' : '' ?>">
                                <td class="ps-3">
                                    <strong><?= htmlspecialchars($u['full_name'] ?: $u['username']) ?></strong><br>
                                    <small class="text-muted">Username: <code><?= htmlspecialchars($u['username']) ?></code></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($u['reg_nrp'] ?: '-') ?></span>
                                </td>
                                <td class="small text-secondary"><?= htmlspecialchars($u['email'] ?: '-') ?></td>
                                <td>
                                    <?php if ($u['linked_name']): ?>
                                        <span class="text-dark small fw-semibold"><?= htmlspecialchars($u['linked_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum tertaut</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $u['role'] === 'admin' ? 'bg-dark text-white' : 'bg-light text-dark border' ?>"><?= strtoupper($u['role']) ?></span>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'pending'): ?>
                                        <span class="badge bg-light text-dark border">Pending</span>
                                    <?php elseif ($u['status'] === 'approved'): ?>
                                        <span class="badge bg-dark text-white">Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge bg-white text-dark border border-dark">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3 text-center">
                                    <?php if ($u['status'] === 'pending'): ?>
                                        <form method="POST" action="akun.php" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-dark fw-semibold">Setujui</button>
                                        </form>
                                        <form method="POST" action="akun.php" class="d-inline ms-1">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-outline-dark">Tolak</button>
                                        </form>
                                    <?php else: ?>
                                        <?php if ($u['role'] !== 'admin'): ?>
                                            <form method="POST" action="akun.php" class="d-inline" onsubmit="return confirm('Reset password akun ini menjadi papk123?');">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="action" value="reset_password">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Reset Password">
                                                    Reset Password
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Tidak ada akun dalam daftar ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
