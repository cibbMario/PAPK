<?php
// user/profil.php
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

$userId = $_SESSION['user_id'];

// Handle Upload Foto Profil User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Validasi tipe file & ukuran maksimal 2MB
        if (!in_array($ext, $allowedExts)) {
            setFlash('Format file tidak didukung! Gunakan file gambar (JPG, PNG, WEBP).', 'warning');
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            setFlash('Ukuran foto terlalu besar! Maksimal ukuran file adalah 2 MB.', 'warning');
        } else {
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../uploads/avatars/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $photoPath = 'uploads/avatars/' . $filename;

                // Update foto di users & members (jika terhubung)
                $updUser = $pdo->prepare("UPDATE users SET photo_path = :photo WHERE id = :id");
                $updUser->execute([':photo' => $photoPath, ':id' => $userId]);

                $memberId = $pdo->query("SELECT member_id FROM users WHERE id = $userId")->fetchColumn();
                if ($memberId) {
                    $updMember = $pdo->prepare("UPDATE members SET photo_path = :photo WHERE id = :mid");
                    $updMember->execute([':photo' => $photoPath, ':mid' => $memberId]);
                }

                setFlash('Foto profil Anda berhasil diperbarui!', 'success');
                header('Location: profil.php');
                exit;
            } else {
                setFlash('Gagal menyimpan foto ke server.', 'danger');
            }
        }
    } else {
        setFlash('Silakan pilih file foto terlebih dahulu.', 'warning');
    }
    header('Location: profil.php');
    exit;
}

// Ambil data user beserta data anggota yang terhubung
$stmt = $pdo->prepare("SELECT u.*, m.nrp_nia, m.name, m.rank, m.unit, m.phone, m.address, m.status as member_status, m.photo_path as member_photo 
                      FROM users u 
                      LEFT JOIN members m ON u.member_id = m.id 
                      WHERE u.id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$displayPhoto = $user['photo_path'] ?: ($user['member_photo'] ?: null);
$avatarUrl = (!empty($displayPhoto) && file_exists(__DIR__ . '/../' . $displayPhoto)) 
             ? '../' . htmlspecialchars($displayPhoto) 
             : "https://ui-avatars.com/api/?name=" . urlencode($user['name'] ?: $user['username']) . "&size=128&background=1e3c72&color=fff";
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Profil Saya</h1>
    <a href="ajukan-perubahan.php" class="btn btn-outline-primary btn-sm">Ajukan Perubahan Data</a>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 text-center p-3">
            <div class="card-body">
                <div class="position-relative d-inline-block mb-3">
                    <img src="<?= $avatarUrl ?>" class="rounded-circle img-fluid border border-3 border-primary shadow-sm" 
                         style="width: 130px; height: 130px; object-fit: cover;" alt="Foto Profil">
                </div>

                <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['name'] ?: $user['username']) ?></h5>
                <p class="text-muted small mb-2"><?= htmlspecialchars($user['rank'] ?? 'Perwira') ?> - <?= htmlspecialchars($user['unit'] ?? 'TNI AU') ?></p>
                <span class="badge bg-success mb-2"><?= strtoupper($user['member_status'] ?? 'AKTIF') ?></span>
                <p class="text-muted small mb-3">NRP/NIA: <?= htmlspecialchars($user['nrp_nia'] ?? '-') ?></p>

                <!-- Tombol Ganti Foto -->
                <button class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                    <i class="bi bi-camera-fill me-1"></i> Ganti Foto Profil
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Informasi Data Keanggotaan</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th style="width: 30%;">NRP / NIA</th>
                        <td>: <?= htmlspecialchars($user['nrp_nia'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Pangkat / Korps</th>
                        <td>: <?= htmlspecialchars($user['rank'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Satuan Kerja</th>
                        <td>: <?= htmlspecialchars($user['unit'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Nomor HP / WA</th>
                        <td>: <?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Alamat Domisili</th>
                        <td>: <?= htmlspecialchars($user['address'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Email Akun</th>
                        <td>: <?= htmlspecialchars($user['email'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <?php
        // Ambil riwayat kompetensi singkat perwira
        $userMemberId = $user['member_id'] ?? null;
        $userComps = [];
        if ($userMemberId) {
            $stmtC = $pdo->prepare("SELECT * FROM member_competencies WHERE member_id = :mid ORDER BY year DESC, id DESC LIMIT 5");
            $stmtC->execute([':mid' => $userMemberId]);
            $userComps = $stmtC->fetchAll();
        }
        ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-award text-primary me-2"></i>Portofolio & Kompetensi Terverifikasi</h5>
                <a href="kompetensi.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i> Kelola Portofolio</a>
            </div>
            <div class="card-body">
                <?php if (count($userComps) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($userComps as $uc): ?>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary me-2 text-capitalize"><?= str_replace('_', ' ', $uc['category']) ?></span>
                                    <strong><?= htmlspecialchars($uc['title']) ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($uc['institution'] ?: 'Lembaga Kedinasan') ?> (<?= htmlspecialchars($uc['year'] ?: '-') ?>)</div>
                                </div>
                                <?php if (!empty($uc['certificate_no'])): ?>
                                    <span class="badge bg-light text-dark border"><code><?= htmlspecialchars($uc['certificate_no']) ?></code></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">Belum ada portofolio atau sertifikasi keahlian yang ditambahkan. Klik tombol <strong>Kelola Portofolio</strong> untuk melengkapi data Anda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Upload Foto -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="profil.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Unggah Foto Profil Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <img src="<?= $avatarUrl ?>" class="rounded-circle mb-3 border shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                        <input class="form-control" type="file" name="avatar" accept="image/*" required>
                        <small class="text-muted d-block mt-2">Maksimal 2 MB (Format: JPG, PNG, WEBP).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Simpan Foto Profil</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
