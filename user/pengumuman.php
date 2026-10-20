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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2 font-serif text-dark mb-1">Pusat Informasi & Pengumuman</h1>
        <p class="text-muted small mb-0">Warta resmi, info perwira baru, dan infografis perkembangan karier PAPK TNI AU.</p>
    </div>
</div>

<!-- Tab Kategori Filter Monochrome -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="pengumuman.php?cat=all" class="btn btn-sm <?= $category === 'all' ? 'btn-dark' : 'btn-outline-dark' ?>">
        Semua Informasi
    </a>
    <a href="pengumuman.php?cat=perwira_baru" class="btn btn-sm <?= $category === 'perwira_baru' ? 'btn-dark' : 'btn-outline-dark' ?>">
        Info Perwira Baru (<?= $countPerwiraBaru ?>)
    </a>
    <a href="pengumuman.php?cat=infografis" class="btn btn-sm <?= $category === 'infografis' ? 'btn-dark' : 'btn-outline-dark' ?>">
        Infografis & Data (<?= $countInfografis ?>)
    </a>
    <a href="pengumuman.php?cat=pengumuman" class="btn btn-sm <?= $category === 'pengumuman' ? 'btn-dark' : 'btn-outline-dark' ?>">
        Edaran Kedinasan (<?= $countPengumuman ?>)
    </a>
</div>

<div class="row">
    <div class="col-lg-9">
        <?php if (!empty($announcements)): ?>
            <?php foreach ($announcements as $ann): ?>
                <?php 
                    $catTitle = 'Pengumuman';
                    if ($ann['category'] === 'perwira_baru') {
                        $catTitle = 'Info Perwira Baru';
                    } elseif ($ann['category'] === 'infografis') {
                        $catTitle = 'Infografis & Visual Data';
                    }
                ?>
                <div class="card border mb-4 overflow-hidden" style="border-radius: 12px;">
                    <div class="card-header bg-white border-bottom pt-3 px-4 d-flex justify-content-between align-items-center">
                        <span class="badge bg-dark text-white px-3 py-2 fw-semibold"><?= $catTitle ?></span>
                        <small class="text-muted"><?= date('d F Y', strtotime($ann['created_at'])) ?></small>
                    </div>

                    <?php if (!empty($ann['image_path']) && file_exists(__DIR__ . '/../' . $ann['image_path'])): ?>
                        <img src="../<?= htmlspecialchars($ann['image_path']) ?>" class="card-img-top img-fluid" style="max-height: 400px; object-fit: cover;" alt="Infografis">
                    <?php endif; ?>

                    <div class="card-body p-4">
                        <h4 class="card-title fw-bold text-dark font-serif mb-2"><?= htmlspecialchars($ann['title']) ?></h4>
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
            <div class="card border p-4 text-center bg-white" style="border-radius: 12px;">
                <p class="text-muted mb-0">Tidak ada pengumuman atau konten pada kategori ini.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Widget Info -->
    <div class="col-lg-3">
        <div class="card border mb-4 bg-light" style="border-radius: 12px;">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-2 text-dark font-serif">Kanal Resmi</h6>
                <p class="text-muted small mb-0">
                    Seluruh informasi resmi pembinaan profesi dan karier perwira bersumber langsung dari Disminpersau / Spersau.
                </p>
            </div>
        </div>

        <div class="card border p-3 bg-white" style="border-radius: 12px;">
            <h6 class="fw-bold mb-3 font-serif">Statistik Konten</h6>
            <ul class="list-unstyled small text-muted mb-0">
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span>Perwira Baru</span>
                    <strong class="text-dark"><?= $countPerwiraBaru ?></strong>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span>Infografis</span>
                    <strong class="text-dark"><?= $countInfografis ?></strong>
                </li>
                <li class="d-flex justify-content-between py-2">
                    <span>Edaran Dinas</span>
                    <strong class="text-dark"><?= $countPengumuman ?></strong>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
