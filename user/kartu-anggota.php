<?php
// user/kartu-anggota.php
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kartu Tanda Anggota PAPK</h1>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Cetak Kartu</button>
</div>

<div class="d-flex justify-content-center my-4">
    <div class="card shadow border-0" style="width: 480px; height: 280px; border-radius: 16px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; position: relative; overflow: hidden;">
        <div style="position: absolute; right: -40px; top: -40px; width: 180px; height: 180px; background: rgba(255,255,255,0.06); border-radius: 50%;"></div>
        <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-center border-bottom border-light border-opacity-25 pb-2">
                <div>
                    <h5 class="mb-0 fw-bold tracking-wide">KARTU ANGGOTA PAPK</h5>
                    <small class="opacity-75">TNI ANGKATAN UDARA</small>
                </div>
                <span class="badge bg-warning text-dark fw-bold">RESMI</span>
            </div>
            
            <div class="d-flex align-items-center my-2">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&size=80&background=fff&color=1e3c72" class="rounded-3 shadow-sm me-3 border border-2 border-white" alt="Foto">
                <div>
                    <h4 class="mb-0 fw-bold"><?= strtoupper(htmlspecialchars($username)) ?></h4>
                    <p class="mb-0 small opacity-75">ID ANGGOTA: PAPK-AU-<?= str_pad($userId, 4, '0', STR_PAD_LEFT) ?></p>
                    <p class="mb-0 small opacity-75">STATUS: AKTIF</p>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-end pt-2 border-top border-light border-opacity-25 small opacity-75">
                <span>Berlaku Selama Menjadi Anggota Aktif</span>
                <span>Mabesau Cilangkap</span>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
