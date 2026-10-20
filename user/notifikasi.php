<?php
// user/notifikasi.php
// Pusat Notifikasi Personel PAPK TNI AU
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

$userId = $_SESSION['user_id'];

// Handle Tandai Semua Dibaca
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
    $stmt->execute([':uid' => $userId]);
    setFlash('Semua notifikasi telah ditandai sebagai dibaca.', 'info');
    header('Location: notifikasi.php');
    exit;
}

// Handle Tandai Satu Dibaca
if (isset($_GET['action']) && $_GET['action'] === 'read' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $id, ':uid' => $userId]);
    header('Location: notifikasi.php');
    exit;
}

// Ambil Semua Notifikasi User Ini
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY id DESC LIMIT 50");
$stmt->execute([':uid' => $userId]);
$notifications = $stmt->fetchAll();

$unreadCount = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = $userId AND is_read = 0")->fetchColumn();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1"><i class="bi bi-bell-fill text-warning me-2"></i>Pusat Notifikasi Kedinasan</h1>
        <p class="text-muted small mb-0">Riwayat pemberitahuan persetujuan data, edaran resmi, dan pengumuman kedinasan.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($unreadCount > 0): ?>
            <a href="notifikasi.php?action=mark_all_read" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-9 mx-auto">
        <?php if (count($notifications) > 0): ?>
            <div class="list-group shadow-sm border-0 mb-4">
                <?php foreach ($notifications as $n): 
                    $isUnread = ($n['is_read'] == 0);
                    $icon = 'bi-info-circle-fill text-primary';
                    if (strpos($n['type'], 'approved') !== false) {
                        $icon = 'bi-check-circle-fill text-success';
                    } elseif (strpos($n['type'], 'rejected') !== false) {
                        $icon = 'bi-x-circle-fill text-danger';
                    } elseif (strpos($n['type'], 'pengumuman') !== false) {
                        $icon = 'bi-megaphone-fill text-warning';
                    }
                ?>
                    <div class="list-group-item list-group-item-action p-3 d-flex align-items-start <?= $isUnread ? 'bg-light border-start border-4 border-primary' : '' ?>">
                        <div class="fs-4 me-3 mt-1"><i class="bi <?= $icon ?>"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                <span class="badge bg-secondary text-uppercase" style="font-size: 10px;"><?= htmlspecialchars($n['type']) ?></span>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($n['created_at'])) ?> WIB</small>
                            </div>
                            <p class="mb-1 text-dark" style="white-space: pre-wrap;"><?= htmlspecialchars($n['message']) ?></p>
                            <?php if ($isUnread): ?>
                                <a href="notifikasi.php?action=read&id=<?= $n['id'] ?>" class="btn btn-sm btn-link text-decoration-none p-0" style="font-size: 11px;">
                                    Tandai telah dibaca
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0 py-5 text-center">
                <div class="card-body">
                    <i class="bi bi-bell-slash fs-1 text-muted opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-muted">Belum Ada Notifikasi</h5>
                    <p class="text-muted small">Pemberitahuan persetujuan data dan pengumuman dinas baru akan ditampilkan di sini.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
