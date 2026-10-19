<?php
// user/profil.php
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

$userId = $_SESSION['user_id'];

// Ambil data user beserta data anggota yang terhubung
$stmt = $pdo->prepare("SELECT u.*, m.nrp_nia, m.name, m.rank, m.unit, m.phone, m.address, m.status as member_status 
                      FROM users u 
                      LEFT JOIN members m ON u.member_id = m.id 
                      WHERE u.id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Profil Saya</h1>
    <a href="ajukan-perubahan.php" class="btn btn-outline-primary btn-sm">Ajukan Perubahan Data</a>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 text-center p-3">
            <div class="card-body">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name'] ?: $user['username']) ?>&size=128&background=1e3c72&color=fff" class="rounded-circle img-fluid mb-3" alt="Foto Profil">
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['name'] ?: $user['username']) ?></h5>
                <p class="text-muted small mb-2"><?= htmlspecialchars($user['rank'] ?? 'Perwira') ?> - <?= htmlspecialchars($user['unit'] ?? 'TNI AU') ?></p>
                <span class="badge bg-success mb-2"><?= strtoupper($user['member_status'] ?? 'AKTIF') ?></span>
                <p class="text-muted small mb-0">NRP/NIA: <?= htmlspecialchars($user['nrp_nia'] ?? '-') ?></p>
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
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
