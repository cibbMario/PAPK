<?php
// user/pengumuman.php
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

$category = $_GET['cat'] ?? 'all';
$params = [];
$sql = "SELECT a.*, u.username as author_name 
        FROM announcements a 
        LEFT JOIN users u ON a.created_by = u.id ";

if (in_array($category, ['pengumuman', 'perwira_baru', 'infografis'])) {
    $sql .= "WHERE a.category = :cat ";
    $params[':cat'] = $category;
}

$sql .= "ORDER BY a.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$announcements = $stmt->fetchAll();

// Hitung per kategori
$countPerwiraBaru = $pdo->query("SELECT COUNT(*) FROM announcements WHERE category = 'perwira_baru'")->fetchColumn();
$countInfografis  = $pdo->query("SELECT COUNT(*) FROM announcements WHERE category = 'infografis'")->fetchColumn();
$countPengumuman  = $pdo->query("SELECT COUNT(*) FROM announcements WHERE category = 'pengumuman'")->fetchColumn();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 fw-bold text-dark mb-1">Pusat Informasi & Pengumuman Personel</h1>
        <p class="text-muted small mb-0">Warta resmi, info perwira baru, dan infografis perkembangan karier PAPK TNI AU.</p>
    </div>
</div>

<!-- Tab Kategori Filter -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="pengumuman.php?cat=all" class="btn btn-sm <?= $category === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">
        Semua Informasi
    </a>
    <a href="pengumuman.php?cat=perwira_baru" class="btn btn-sm <?= $category === 'perwira_baru' ? 'btn-success text-white' : 'btn-outline-success' ?>">
        <i class="bi bi-person-badge me-1"></i> Info Perwira Baru (<?= $countPerwiraBaru ?>)
    </a>
    <a href="pengumuman.php?cat=infografis" class="btn btn-sm <?= $category === 'infografis' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">
        <i class="bi bi-bar-chart-fill me-1"></i> Infografis & Data (<?= $countInfografis ?>)
    </a>
    <a href="pengumuman.php?cat=pengumuman" class="btn btn-sm <?= $category === 'pengumuman' ? 'btn-info text-white' : 'btn-outline-info' ?>">
        <i class="bi bi-megaphone me-1"></i> Edaran Kedinasan (<?= $countPengumuman ?>)
    </a>
</div>

<div class="row">
    <div class="col-lg-9">
        <?php if (!empty($announcements)): ?>
            <?php foreach ($announcements as $ann): ?>
                <?php 
                    $catBadge = 'bg-secondary';
                    $catTitle = 'Pengumuman';
                    if ($ann['category'] === 'perwira_baru') {
                        $catBadge = 'bg-success';
                        $catTitle = 'Info Perwira Baru';
                    } elseif ($ann['category'] === 'infografis') {
                        $catBadge = 'bg-warning text-dark';
                        $catTitle = 'Infografis & Visual Data';
                    }
                ?>
                <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                        <span class="badge <?= $catBadge ?> px-3 py-2 fw-semibold"><?= $catTitle ?></span>
                        <small class="text-muted"><i class="bi bi-calendar3"></i> <?= date('d F Y', strtotime($ann['created_at'])) ?></small>
                    </div>

                    <?php if (!empty($ann['image_path']) && file_exists(__DIR__ . '/../' . $ann['image_path'])): ?>
                        <img src="../<?= htmlspecialchars($ann['image_path']) ?>" class="card-img-top img-fluid" style="max-height: 400px; object-fit: cover;" alt="Infografis">
                    <?php endif; ?>

                    <div class="card-body p-4">
                        <h4 class="card-title fw-bold text-dark mb-2"><?= htmlspecialchars($ann['title']) ?></h4>
                        <p class="text-muted small mb-3">
                            Dipublikasikan oleh: <strong><?= htmlspecialchars($ann['author_name'] ?? 'Mabesau / Staf Admin') ?></strong>
                        </p>
                        <hr class="opacity-25 my-3">
                        <div class="card-text text-secondary" style="line-height: 1.8; font-size: 15px;">
                            <?= nl2br(htmlspecialchars($ann['content'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card border-0 shadow-sm p-4 text-center">
                <p class="text-muted mb-0">Tidak ada pengumuman atau konten pada kategori ini.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Widget Info -->
    <div class="col-lg-3">
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-2 text-primary"><i class="bi bi-shield-check"></i> Kanal Resmi</h6>
                <p class="text-muted small mb-0">
                    Seluruh informasi resmi pembinaan profesi dan karier perwira bersumber langsung dari Disminpersau / Spersau.
                </p>
            </div>
        </div>

        <div class="card shadow-sm border-0 p-3">
            <h6 class="fw-bold mb-2">Statistik Konten</h6>
            <ul class="list-unstyled small text-muted mb-0">
                <li class="d-flex justify-content-between py-1 border-bottom">
                    <span>Perwira Baru</span>
                    <strong class="text-success"><?= $countPerwiraBaru ?></strong>
                </li>
                <li class="d-flex justify-content-between py-1 border-bottom">
                    <span>Infografis</span>
                    <strong class="text-warning"><?= $countInfografis ?></strong>
                </li>
                <li class="d-flex justify-content-between py-1">
                    <span>Edaran Dinas</span>
                    <strong class="text-info"><?= $countPengumuman ?></strong>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
