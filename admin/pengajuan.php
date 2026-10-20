<?php
// admin/pengajuan.php
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

// Handle Persetujuan / Penolakan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);
    $adminNote = trim($_POST['admin_note'] ?? '');

    $stmtReq = $pdo->prepare("SELECT * FROM change_requests WHERE id = :id AND status = 'pending'");
    $stmtReq->execute([':id' => $requestId]);
    $req = $stmtReq->fetch();

    if ($req) {
        require_once __DIR__ . '/../includes/notifier.php';
        $fieldNames = [
            'phone'   => 'Nomor HP / WhatsApp',
            'address' => 'Alamat Domisili',
            'name'    => 'Nama Lengkap & Gelar'
        ];
        $fieldLabel = $fieldNames[$req['field']] ?? $req['field'];

        if ($action === 'approve') {
            // 1. Update status di change_requests
            $updReq = $pdo->prepare("UPDATE change_requests SET status = 'approved', admin_note = :note WHERE id = :id");
            $updReq->execute([':note' => $adminNote ?: 'Disetujui oleh Administrator', ':id' => $requestId]);

            // 2. Update data di tabel members secara otomatis
            $allowedColumns = ['phone', 'address', 'name'];
            if (in_array($req['field'], $allowedColumns)) {
                $column = $req['field'];
                $updMember = $pdo->prepare("UPDATE members SET `$column` = :val WHERE id = :mid");
                $updMember->execute([':val' => $req['new_value'], ':mid' => $req['member_id']]);
            }

            // 3. Kirim notifikasi WhatsApp & In-App ke pemohon
            sendNotificationToUser(
                $req['requested_by'],
                'Pengajuan Data Disetujui',
                "Pengajuan pembaruan data ($fieldLabel) Anda telah DISETUJUI oleh Administrator.\nCatatan: " . ($adminNote ?: 'Disetujui oleh Administrator'),
                'approval'
            );

            setFlash('Pengajuan berhasil DISETUJUI, data diperbarui, dan notifikasi telah dikirim ke pemohon!', 'success');
        } elseif ($action === 'reject') {
            $updReq = $pdo->prepare("UPDATE change_requests SET status = 'rejected', admin_note = :note WHERE id = :id");
            $updReq->execute([':note' => $adminNote ?: 'Pengajuan ditolak', ':id' => $requestId]);

            // Kirim notifikasi penolakan
            sendNotificationToUser(
                $req['requested_by'],
                'Pengajuan Data Ditolak',
                "Mohon maaf, pengajuan pembaruan data ($fieldLabel) Anda DITOLAK oleh Administrator.\nAlasan: " . ($adminNote ?: 'Dokumen/data tidak sesuai persyaratan.'),
                'rejection'
            );

            setFlash('Pengajuan DITOLAK dan notifikasi telah dikirim ke pemohon.', 'danger');
        }
    }
    header('Location: pengajuan.php');
    exit;
}

// Ambil daftar pengajuan
$statusFilter = $_GET['status'] ?? 'all';
$sql = "SELECT cr.*, m.name as member_name, m.nrp_nia, u.username 
        FROM change_requests cr 
        JOIN members m ON cr.member_id = m.id 
        JOIN users u ON cr.requested_by = u.id ";
$params = [];

if ($statusFilter === 'pending') {
    $sql .= "WHERE cr.status = 'pending' ";
} elseif ($statusFilter === 'approved') {
    $sql .= "WHERE cr.status = 'approved' ";
} elseif ($statusFilter === 'rejected') {
    $sql .= "WHERE cr.status = 'rejected' ";
}

$sql .= "ORDER BY cr.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$fieldNames = [
    'phone' => 'Nomor HP / WhatsApp',
    'address' => 'Alamat Domisili',
    'name' => 'Nama Lengkap & Gelar'
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Verifikasi Pengajuan Perubahan Data</h1>
    <div class="btn-group" role="group">
        <a href="pengajuan.php?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">Semua</a>
        <a href="pengajuan.php?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">Pending</a>
        <a href="pengajuan.php?status=approved" class="btn btn-sm <?= $statusFilter === 'approved' ? 'btn-success' : 'btn-outline-success' ?>">Disetujui</a>
        <a href="pengajuan.php?status=rejected" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">Ditolak</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tgl Pengajuan</th>
                        <th>Anggota (NRP)</th>
                        <th>Kolom Data</th>
                        <th>Data Lama</th>
                        <th>Data Baru Diajukan</th>
                        <th>Status</th>
                        <th class="pe-3 text-center">Aksi Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td class="ps-3 small text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['member_name']) ?></strong><br>
                                    <small class="text-muted">NRP: <?= htmlspecialchars($r['nrp_nia']) ?> (User: <?= htmlspecialchars($r['username']) ?>)</small>
                                </td>
                                <td><span class="badge bg-secondary"><?= $fieldNames[$r['field']] ?? $r['field'] ?></span></td>
                                <td class="text-secondary small"><?= htmlspecialchars($r['old_value'] ?: '-') ?></td>
                                <td class="fw-semibold text-primary"><?= htmlspecialchars($r['new_value']) ?></td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($r['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3 text-center">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#actionModal" 
                                                data-id="<?= $r['id'] ?>" data-action="approve" data-name="<?= htmlspecialchars($r['member_name']) ?>">
                                            Setujui
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#actionModal" 
                                                data-id="<?= $r['id'] ?>" data-action="reject" data-name="<?= htmlspecialchars($r['member_name']) ?>">
                                            Tolak
                                        </button>
                                    <?php else: ?>
                                        <small class="text-muted"><?= htmlspecialchars($r['admin_note'] ?: '-') ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ada data pengajuan dalam kategori ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Verifikasi -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="pengajuan.php">
                <input type="hidden" name="request_id" id="modalRequestId">
                <input type="hidden" name="action" id="modalActionType">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Konfirmasi Aksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="modalConfirmText">Apakah Anda yakin?</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan Admin (Opsional):</label>
                        <textarea class="form-control" name="admin_note" rows="2" placeholder="Tulis alasan atau catatan untuk anggota..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
const actionModal = document.getElementById('actionModal');
actionModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const reqId = button.getAttribute('data-id');
    const act = button.getAttribute('data-action');
    const memName = button.getAttribute('data-name');

    document.getElementById('modalRequestId').value = reqId;
    document.getElementById('modalActionType').value = act;

    const title = document.getElementById('modalTitle');
    const confirmText = document.getElementById('modalConfirmText');
    const submitBtn = document.getElementById('modalSubmitBtn');

    if (act === 'approve') {
        title.textContent = 'Setujui Pengajuan Perubahan';
        confirmText.innerHTML = `Setujui perubahan data untuk <strong>${memName}</strong>? Data di tabel anggota akan langsung otomatis diperbarui.`;
        submitBtn.className = 'btn btn-success';
        submitBtn.textContent = 'Ya, Setujui';
    } else {
        title.textContent = 'Tolak Pengajuan Perubahan';
        confirmText.innerHTML = `Tolak pengajuan perubahan data untuk <strong>${memName}</strong>?`;
        submitBtn.className = 'btn btn-danger';
        submitBtn.textContent = 'Ya, Tolak';
    }
});
</script>
