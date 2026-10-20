<?php
// user/riwayat-pengajuan.php
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

$userId = $_SESSION['user_id'];

// Ambil riwayat pengajuan milik user ini
$stmt = $pdo->prepare("SELECT cr.*, m.name as member_name, m.nrp_nia 
                      FROM change_requests cr 
                      JOIN members m ON cr.member_id = m.id 
                      WHERE cr.requested_by = :uid 
                      ORDER BY cr.id DESC");
$stmt->execute([':uid' => $userId]);
$requests = $stmt->fetchAll();

$fieldNames = [
    'phone' => 'Nomor HP / WA',
    'address' => 'Alamat Domisili',
    'name' => 'Nama Lengkap'
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 font-serif">Riwayat Pengajuan Perubahan Data</h1>
    <a href="ajukan-perubahan.php" class="btn btn-dark btn-sm">+ Ajukan Baru</a>
</div>

<div class="card border" style="border-radius: 12px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Data Diubah</th>
                        <th>Data Lama</th>
                        <th>Data Baru</th>
                        <th>Status</th>
                        <th class="pe-3">Catatan Admin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td class="ps-3 text-muted small"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $fieldNames[$r['field']] ?? $r['field'] ?></span></td>
                                <td class="text-secondary small"><?= htmlspecialchars($r['old_value'] ?: '-') ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($r['new_value']) ?></td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <span class="badge bg-light text-dark border">Pending</span>
                                    <?php elseif ($r['status'] === 'approved'): ?>
                                        <span class="badge bg-dark text-white">Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge bg-white text-dark border border-dark">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3 small text-muted"><?= htmlspecialchars($r['admin_note'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Belum ada pengajuan perubahan data.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
