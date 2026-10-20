<?php
// user/dashboard.php
// Direktori Personel Angkatan PAPK TNI AU (Matra Udara)
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

// Parameter Filter & Pencarian
$search  = trim($_GET['search'] ?? '');
$korps   = trim($_GET['korps'] ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 24;
$offset  = ($page - 1) * $perPage;

$korpsList = [
    'Sus' => 'Korps Khusus (Sus)',
    'Tek' => 'Korps Teknik (Tek)',
    'Lek' => 'Korps Elektronika (Lek)',
    'Kes' => 'Korps Kesehatan (Kes)',
    'Kal' => 'Korps Pembekalan (Kal)',
    'Adm' => 'Korps Administrasi (Adm)'
];

// Hitung total personel per korps
$korpsCounts = [];
$totalAll = 0;
$stmtK = $pdo->query("SELECT korps, COUNT(*) as cnt FROM members WHERE status = 'aktif' GROUP BY korps");
while ($r = $stmtK->fetch()) {
    $korpsCounts[$r['korps']] = (int)$r['cnt'];
    $totalAll += (int)$r['cnt'];
}

// Filter Query
$where = "WHERE status = 'aktif'";
$params = [];

if ($search !== '') {
    $where .= " AND (name LIKE :kw OR nrp_nia LIKE :kw OR unit LIKE :kw OR jabatan LIKE :kw)";
    $params[':kw'] = "%$search%";
}

if ($korps !== '' && isset($korpsList[$korps])) {
    $where .= " AND korps = :korps";
    $params[':korps'] = $korps;
}

// Hitung total hasil filter
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM members $where");
$stmtCount->execute($params);
$totalFiltered = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalFiltered / $perPage));

// Ambil Data Personel
$sql = "SELECT id, nrp_nia, name, `rank`, korps, unit, jabatan, location_name, phone, address, photo_path 
        FROM members $where 
        ORDER BY id ASC 
        LIMIT :limit OFFSET :offset";
$stmtData = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmtData->bindValue($k, $v);
}
$stmtData->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtData->execute();
$members = $stmtData->fetchAll();
?>

<style>
/* Tipografi Elegan: Editorial Serif untuk Judul, Geometric Sans untuk Isi */
.editorial-header {
    text-align: center;
    padding: 40px 20px 28px;
    margin-bottom: 24px;
}

.editorial-tag {
    font-family: var(--font-sans);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #64748b;
    margin-bottom: 8px;
    display: inline-block;
}

.editorial-title {
    font-family: var(--font-serif);
    font-size: 2.85rem;
    font-weight: 600;
    letter-spacing: -0.5px;
    color: #0f172a;
    line-height: 1.15;
    margin-bottom: 10px;
}

.editorial-subtitle {
    font-family: var(--font-sans);
    font-size: 0.95rem;
    color: #64748b;
    font-weight: 400;
    max-width: 520px;
    margin: 0 auto;
}

/* Saringan & Kontrol Bersih Tanpa Ikon */
.filter-container {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 24px;
    margin-bottom: 30px;
}

.clean-input {
    font-family: var(--font-sans);
    font-size: 0.92rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 14px;
    color: #0f172a;
    width: 100%;
    transition: border-color 0.15s ease;
}

.clean-input:focus {
    border-color: #0f172a;
    outline: none;
    box-shadow: none;
}

.clean-btn {
    font-family: var(--font-sans);
    font-size: 0.88rem;
    font-weight: 600;
    background: #0f172a;
    color: #ffffff;
    border: 1px solid #0f172a;
    border-radius: 8px;
    padding: 10px 20px;
    transition: background 0.15s ease;
}

.clean-btn:hover {
    background: #334155;
    color: #ffffff;
}

.tab-filter {
    font-family: var(--font-sans);
    font-size: 0.82rem;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.15s ease;
}

.tab-filter:hover {
    color: #0f172a;
    background: #f1f5f9;
}

.tab-filter.active {
    color: #0f172a;
    background: #e2e8f0;
    font-weight: 700;
}

/* Kartu Personel Sederhana & Berhierarki Kuat */
.officer-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 22px 18px;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.officer-item:hover {
    border-color: #94a3b8;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
}

.officer-photo {
    width: 76px;
    height: 76px;
    object-fit: cover;
    border-radius: 50%;
    border: 1px solid #e2e8f0;
    margin: 0 auto 12px;
    display: block;
}

.officer-korps-label {
    font-family: var(--font-sans);
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #64748b;
    margin-bottom: 4px;
    text-align: center;
}

.officer-name-title {
    font-family: var(--font-sans);
    font-size: 0.98rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
    text-align: center;
    margin-bottom: 8px;
}

.officer-jabatan-text {
    font-family: var(--font-sans);
    font-size: 0.84rem;
    font-weight: 500;
    color: #334155;
    text-align: center;
    margin-bottom: 2px;
}

.officer-satuan-text {
    font-family: var(--font-sans);
    font-size: 0.78rem;
    color: #64748b;
    text-align: center;
}

.officer-footer-meta {
    margin-top: auto;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.nrp-text {
    font-family: var(--font-sans);
    font-size: 0.76rem;
    font-weight: 600;
    color: #94a3b8;
    letter-spacing: 0.5px;
}

.action-link {
    font-family: var(--font-sans);
    font-size: 0.8rem;
    font-weight: 600;
    color: #0f172a;
    text-decoration: none;
    background: transparent;
    border: none;
    padding: 0;
    transition: color 0.15s ease;
}

.action-link:hover {
    color: #000000;
    text-decoration: underline;
}

.action-link-wa {
    color: #0f172a;
}

.action-link-wa:hover {
    color: #000000;
}

/* Modal Rapi & Editorial */
.modal-title-editorial {
    font-family: var(--font-serif);
    font-size: 1.5rem;
    font-weight: 600;
    color: #0f172a;
}
</style>

<!-- 1. HIERARKI UTAMA: Heading Editorial Serif & Subheading Geometric Sans -->
<div class="editorial-header">
    <div class="editorial-tag">PAPK TNI AU • ANGKATAN XXXII</div>
    <h1 class="editorial-title">Matra AU</h1>
    <p class="editorial-subtitle">
        Buku Direktori Personel • Total <?= $totalAll ?> Orang berpangkat Letda
    </p>
</div>

<!-- 2. HIERARKI KONTROL: Pencarian & Saringan Sederhana -->
<div class="filter-container">
    <form method="GET" action="dashboard.php" class="mb-3">
        <input type="hidden" name="korps" value="<?= htmlspecialchars($korps) ?>">
        <div class="row g-2">
            <div class="col-md-10">
                <input type="text" class="clean-input" name="search" 
                       value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Cari nama (misal: Arniza), korps, jabatan, atau satuan...">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="clean-btn w-100">Cari</button>
                <?php if ($search !== ''): ?>
                    <a href="dashboard.php?korps=<?= urlencode($korps) ?>" class="btn btn-outline-secondary" style="border-radius: 8px;">Reset</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Saringan Korps Tekstual Bersih -->
    <div class="d-flex flex-wrap gap-1 align-items-center pt-2 border-top">
        <span class="small text-muted fw-bold me-2" style="font-size: 11px; letter-spacing: 1px;">KORPS:</span>
        
        <a href="?search=<?= urlencode($search) ?>&korps=" class="tab-filter <?= $korps === '' ? 'active' : '' ?>">
            Semua (<?= $totalAll ?>)
        </a>

        <?php foreach ($korpsList as $kKey => $kName): 
            $cnt = $korpsCounts[$kKey] ?? 0;
        ?>
            <a href="?search=<?= urlencode($search) ?>&korps=<?= $kKey ?>" class="tab-filter <?= $korps === $kKey ? 'active' : '' ?>">
                <?= $kKey ?> (<?= $cnt ?>)
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Ringkasan Keterangan Data -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1">
    <span class="small text-muted">
        Menampilkan <?= count($members) ?> dari <?= $totalFiltered ?> perwira
        <?php if ($korps !== ''): ?>
            — Korps <?= htmlspecialchars($korps) ?>
        <?php endif; ?>
    </span>

    <?php if ($search !== '' || $korps !== ''): ?>
        <a href="dashboard.php" class="small text-muted text-decoration-none">
            Tampilkan Semua Data
        </a>
    <?php endif; ?>
</div>

<!-- 3. HIERARKI KARTU: Grid Direktori Personel (Sesuai Permintaan) -->
<div class="row g-3 mb-5">
    <?php if (count($members) > 0): ?>
        <?php foreach ($members as $m): 
            $avatarUrl = (!empty($m['photo_path']) && file_exists(__DIR__ . '/../' . $m['photo_path'])) 
                         ? '../' . htmlspecialchars($m['photo_path']) 
                         : "https://ui-avatars.com/api/?name=" . urlencode($m['name']) . "&size=100&background=f1f5f9&color=0f172a";
        ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="officer-item">
                    <!-- 1. Pasfoto -->
                    <img src="<?= $avatarUrl ?>" class="officer-photo" alt="<?= htmlspecialchars($m['name']) ?>">

                    <!-- 3. Korps -->
                    <div class="officer-korps-label">
                        KORPS <?= htmlspecialchars($m['korps']) ?>
                    </div>

                    <!-- 2. Nama Lengkap -->
                    <div class="officer-name-title text-truncate" title="<?= htmlspecialchars($m['name']) ?>">
                        <?= htmlspecialchars($m['name']) ?>
                    </div>

                    <!-- 4. Satuan dan Jabatan -->
                    <div class="officer-jabatan-text text-truncate" title="<?= htmlspecialchars($m['jabatan'] ?: 'Perwira Pertama') ?>">
                        <?= htmlspecialchars($m['jabatan'] ?: 'Perwira Pertama') ?>
                    </div>
                    
                    <div class="officer-satuan-text text-truncate" title="<?= htmlspecialchars($m['unit']) ?>">
                        <?= htmlspecialchars($m['unit']) ?>
                    </div>

                    <!-- 5. dll (NRP & Aksi Tekstual) -->
                    <div class="officer-footer-meta">
                        <span class="nrp-text">NRP <?= htmlspecialchars($m['nrp_nia']) ?></span>
                        
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!empty($m['phone'])): 
                                $cleanPhone = preg_replace('/[^0-9]/', '', $m['phone']);
                                if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);
                                $waText = urlencode("Salam, Yth. " . $m['name'] . ". Dari rekan PAPK AU...");
                            ?>
                                <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= $waText ?>" 
                                   target="_blank" 
                                   class="action-link action-link-wa">
                                    WhatsApp
                                </a>
                                <span class="text-muted" style="font-size: 11px;">•</span>
                            <?php endif; ?>
                            
                            <button type="button" 
                                    class="action-link" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalPersonel<?= $m['id'] ?>">
                                Detail
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card border py-5 text-center bg-white" style="border-radius: 12px;">
                <div class="card-body">
                    <h5 class="fw-bold mb-1">Data Tidak Ditemukan</h5>
                    <p class="text-muted small mb-3">Tidak ada perwira yang sesuai dengan kata kunci pencarian Anda.</p>
                    <a href="dashboard.php" class="clean-btn text-decoration-none">Kembali ke Direktori Lengkap</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- 4. Navigasi Halaman Simpel -->
<?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center my-4">
        <nav aria-label="Navigasi Halaman">
            <ul class="pagination pagination-sm">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&korps=<?= urlencode($korps) ?>">
                        Sebelumnya
                    </a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&korps=<?= urlencode($korps) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&korps=<?= urlencode($korps) ?>">
                        Berikutnya
                    </a>
                </li>
            </ul>
        </nav>
    </div>
<?php endif; ?>

<!-- 5. Modal Rinci Personel (Gaya Editorial Rapi) -->
<?php foreach ($members as $m): 
    $avatarUrl = (!empty($m['photo_path']) && file_exists(__DIR__ . '/../' . $m['photo_path'])) 
                 ? '../' . htmlspecialchars($m['photo_path']) 
                 : "https://ui-avatars.com/api/?name=" . urlencode($m['name']) . "&size=120&background=f1f5f9&color=0f172a";
    $kFull = $korpsList[$m['korps']] ?? 'Korps ' . $m['korps'];
?>
    <div class="modal fade" id="modalPersonel<?= $m['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                
                <div class="modal-body text-center px-4 pt-0 pb-4">
                    <img src="<?= $avatarUrl ?>" class="rounded-circle border mb-3" style="width: 88px; height: 88px; object-fit: cover;" alt="Foto">
                    
                    <h5 class="modal-title-editorial mb-1"><?= htmlspecialchars($m['name']) ?></h5>
                    <div class="small text-muted mb-3" style="font-weight: 500;">
                        Pangkat Letda • <?= htmlspecialchars($kFull) ?>
                    </div>

                    <!-- Ringkasan Rinci Personel -->
                    <div class="bg-light p-3 text-start mb-3" style="border-radius: 8px;">
                        <div class="row g-2 small">
                            <div class="col-4 text-muted fw-semibold">NRP / NIA</div>
                            <div class="col-8 fw-semibold">: <?= htmlspecialchars($m['nrp_nia']) ?></div>

                            <div class="col-4 text-muted fw-semibold">Jabatan</div>
                            <div class="col-8 fw-bold text-dark">: <?= htmlspecialchars($m['jabatan'] ?: 'Perwira Pertama') ?></div>

                            <div class="col-4 text-muted fw-semibold">Satuan Kerja</div>
                            <div class="col-8 fw-semibold">: <?= htmlspecialchars($m['unit']) ?></div>

                            <div class="col-4 text-muted fw-semibold">Pangkalan</div>
                            <div class="col-8">: <?= htmlspecialchars($m['location_name'] ?: '-') ?></div>

                            <div class="col-4 text-muted fw-semibold">Alamat</div>
                            <div class="col-8">: <?= htmlspecialchars($m['address'] ?: 'Mess Perwira Lanud') ?></div>

                            <div class="col-4 text-muted fw-semibold">Nomor Kontak</div>
                            <div class="col-8">: <?= htmlspecialchars($m['phone'] ?: '-') ?></div>
                        </div>
                    </div>

                    <?php if (!empty($m['phone'])): 
                        $cleanPhone = preg_replace('/[^0-9]/', '', $m['phone']);
                        if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);
                    ?>
                        <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="clean-btn d-block text-decoration-none text-center py-2">
                            Hubungi via WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
