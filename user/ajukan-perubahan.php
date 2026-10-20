<?php
// user/ajukan-perubahan.php
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

$userId = $_SESSION['user_id'];

// Ambil data member yang terhubung dengan user ini
$stmtUser = $pdo->prepare("SELECT u.*, m.nrp_nia, m.name, m.rank, m.unit, m.phone, m.address, m.status as member_status, m.id as mid 
                          FROM users u 
                          LEFT JOIN members m ON u.member_id = m.id 
                          WHERE u.id = :uid");
$stmtUser->execute([':uid' => $userId]);
$userData = $stmtUser->fetch();
$memberId = $userData['mid'] ?? null;

// Handle submit pengajuan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$memberId) {
        setFlash('Akun Anda belum terhubung dengan data anggota!', 'danger');
        header('Location: ajukan-perubahan.php');
        exit;
    }

    $field = trim($_POST['field'] ?? '');
    $newValue = trim($_POST['new_value'] ?? '');
    $oldValue = '';

    // Validasi field yang boleh diajukan
    $allowedFields = [
        'phone' => 'Nomor HP / WhatsApp',
        'address' => 'Alamat Domisili',
        'name' => 'Nama Lengkap & Gelar'
    ];

    if (!array_key_exists($field, $allowedFields) || empty($newValue)) {
        setFlash('Harap isi kolom dan data perubahan dengan benar!', 'warning');
        header('Location: ajukan-perubahan.php');
        exit;
    }

    // Ambil nilai lama
    if ($field === 'phone') $oldValue = $userData['phone'] ?? '';
    elseif ($field === 'address') $oldValue = $userData['address'] ?? '';
    elseif ($field === 'name') $oldValue = $userData['name'] ?? '';

    // Simpan ke change_requests
    $ins = $pdo->prepare("INSERT INTO change_requests (member_id, requested_by, field, old_value, new_value, status) 
                          VALUES (:mid, :req_by, :field, :old, :new, 'pending')");
    $ins->execute([
        ':mid' => $memberId,
        ':req_by' => $userId,
        ':field' => $field,
        ':old' => $oldValue,
        ':new' => $newValue
    ]);

    setFlash('Pengajuan perubahan data berhasil dikirim! Menunggu verifikasi admin.', 'success');
    header('Location: riwayat-pengajuan.php');
    exit;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 font-serif">Form Pengajuan Perubahan Data</h1>
    <a href="riwayat-pengajuan.php" class="btn btn-outline-dark btn-sm">Lihat Riwayat Pengajuan</a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card border mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold font-serif">Ajukan Koreksi / Pembaruan Data</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!$memberId): ?>
                    <div class="alert alert-warning">
                        Akun Anda belum ditautkan ke data anggota oleh Administrator. Silakan hubungi admin.
                    </div>
                <?php else: ?>
                    <form method="POST" action="ajukan-perubahan.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Data yang Ingin Diubah</label>
                            <select class="form-select" name="field" id="selectField" required>
                                <option value="">-- Pilih Bidang Data --</option>
                                <option value="phone" data-current="<?= htmlspecialchars($userData['phone'] ?? '-') ?>">Nomor HP / WhatsApp</option>
                                <option value="address" data-current="<?= htmlspecialchars($userData['address'] ?? '-') ?>">Alamat Domisili</option>
                                <option value="name" data-current="<?= htmlspecialchars($userData['name'] ?? '-') ?>">Nama Lengkap & Gelar</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Data Saat Ini (Tercatat)</label>
                            <input type="text" class="form-control bg-light" id="currentValueDisplay" value="-" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Data Baru yang Diajukan</label>
                            <textarea class="form-control" name="new_value" id="newValueInput" rows="3" placeholder="Ketik data baru yang benar di sini..." required></textarea>
                            <small class="text-muted">Perubahan data akan ditinjau dan diverifikasi terlebih dahulu oleh staf admin.</small>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-dark fw-semibold py-2">Kirim Pengajuan Perubahan</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border bg-light" style="border-radius: 12px;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark font-serif">Alur Verifikasi Data</h6>
                <ol class="small text-muted ps-3 mb-0" style="line-height: 1.8;">
                    <li>Anggota mengisi formulir perubahan data yang ingin diperbarui.</li>
                    <li>Status pengajuan akan berstatus <strong>Pending</strong>.</li>
                    <li>Administrator akan memeriksa keabsahan dan dokumen pendukung.</li>
                    <li>Jika disetujui, data pada profil Anda akan otomatis diperbarui dan status menjadi <strong>Approved</strong>.</li>
                    <li>Jika ditolak, admin akan menyertakan catatan alasan pada riwayat pengajuan.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
document.getElementById('selectField').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const currentVal = selected.getAttribute('data-current') || '-';
    document.getElementById('currentValueDisplay').value = currentVal;
});
</script>
