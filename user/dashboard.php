<?php
// user/dashboard.php
// Buku Direktori Perwira Matra Udara (AU) - PAPK TNI AU
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

// Parameter Filter & Tampilan
$search   = trim($_GET['search'] ?? '');
$korps    = trim($_GET['korps'] ?? '');
$viewMode = trim($_GET['view'] ?? 'grid'); // 'grid' | 'table' | 'map'
$page     = max(1, intval($_GET['page'] ?? 1));
$perPage  = 24;
$offset   = ($page - 1) * $perPage;

// Korps Mapping & Badge Styles
$korpsMeta = [
    'Sus' => ['name' => 'Korps Khusus (Sus)', 'badge' => 'bg-slate', 'color' => '#334155', 'bg_light' => '#f1f5f9'],
    'Tek' => ['name' => 'Korps Teknik (Tek)', 'badge' => 'bg-sky', 'color' => '#0284c7', 'bg_light' => '#e0f2fe'],
    'Lek' => ['name' => 'Korps Elektronika (Lek)', 'badge' => 'bg-indigo', 'color' => '#4f46e5', 'bg_light' => '#e0e7ff'],
    'Kes' => ['name' => 'Korps Kesehatan (Kes)', 'badge' => 'bg-emerald', 'color' => '#059669', 'bg_light' => '#d1fae5'],
    'Kal' => ['name' => 'Korps Pembekalan (Kal)', 'badge' => 'bg-amber', 'color' => '#d97706', 'bg_light' => '#fef3c7'],
    'Adm' => ['name' => 'Korps Administrasi (Adm)', 'badge' => 'bg-rose', 'color' => '#e11d48', 'bg_light' => '#ffe4e6']
];

// Hitung total tiap korps untuk pills filter
$korpsCounts = [];
$totalAll = 0;
$stmtK = $pdo->query("SELECT korps, COUNT(*) as cnt FROM members GROUP BY korps");
while ($r = $stmtK->fetch()) {
    $korpsCounts[$r['korps']] = (int)$r['cnt'];
    $totalAll += (int)$r['cnt'];
}

// Build Query Filter
$where = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $where .= " AND (name LIKE :kw OR nrp_nia LIKE :kw OR unit LIKE :kw OR jabatan LIKE :kw OR location_name LIKE :kw)";
    $params[':kw'] = "%$search%";
}

if ($korps !== '' && isset($korpsMeta[$korps])) {
    $where .= " AND korps = :korps";
    $params[':korps'] = $korps;
}

// Total records
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM members $where");
$stmtCount->execute($params);
$totalFiltered = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalFiltered / $perPage));

// Ambil Data Personel
$sql = "SELECT id, nrp_nia, name, `rank`, korps, unit, jabatan, location_name, latitude, longitude, phone, address, photo_path, status 
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

// Ambil seluruh data koordinat jika mode peta aktif
$mapPoints = [];
if ($viewMode === 'map') {
    $stmtMap = $pdo->query("SELECT id, nrp_nia, name, `rank`, korps, unit, jabatan, location_name, latitude, longitude, phone, photo_path 
                            FROM members 
                            WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
    $mapPoints = $stmtMap->fetchAll();
}
?>

<style>
/* Modern Minimalist UI & Clean Hierarchy */
.hero-header {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 24px;
}
.hero-title {
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: -0.5px;
    color: #0f172a;
}
.filter-pill {
    font-size: 13px;
    font-weight: 600;
    border-radius: 20px;
    padding: 6px 14px;
    transition: all 0.15s ease;
    text-decoration: none;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.filter-pill:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.filter-pill.active {
    background: #1e3c72;
    color: #ffffff;
    border-color: #1e3c72;
}
.filter-pill .pill-badge {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
    background: rgba(0,0,0,0.08);
}
.filter-pill.active .pill-badge {
    background: rgba(255,255,255,0.25);
    color: #ffffff;
}

/* Officer Card (Yearbook Style) */
.officer-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.officer-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    border-color: #cbd5e1;
}
.officer-avatar {
    width: 84px;
    height: 84px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.officer-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
    margin-bottom: 4px;
}
.officer-jabatan {
    font-size: 0.82rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 2px;
}
.officer-satuan {
    font-size: 0.78rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 4px;
}
.card-meta-footer {
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    padding: 10px 16px;
    font-size: 0.78rem;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Korps Badge Styling */
.badge-korps {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
    letter-spacing: 0.2px;
}
.korps-Sus { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
.korps-Tek { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.korps-Lek { background-color: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
.korps-Kes { background-color: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
.korps-Kal { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.korps-Adm { background-color: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }

#mapContainer {
    height: 540px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
</style>

<!-- Leaflet (Hanya dipanggil jika mode peta) -->
<?php if ($viewMode === 'map'): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>

<!-- 1. Hero Header (Hierarki Level 1) -->
<div class="hero-header shadow-sm">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary px-2.5 py-1 text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">PAPK TNI AU</span>
                <span class="badge bg-light text-secondary border px-2.5 py-1" style="font-size: 11px;">Angkatan XXXII</span>
            </div>
            <h1 class="hero-title mb-1">Matra Udara (AU)</h1>
            <p class="text-muted small mb-0">
                Buku Direktori Resmi Personel • <strong>Total <?= $totalAll ?> Perwira Remaja (Letda)</strong> tersebar di seluruh Lanud & Kesatuan Indonesia.
            </p>
        </div>

        <!-- Tombol Pilihan Mode Tampilan (UX Switch) -->
        <div class="btn-group shadow-sm" role="group" aria-label="Mode Tampilan">
            <a href="?search=<?= urlencode($search) ?>&korps=<?= urlencode($korps) ?>&view=grid" 
               class="btn btn-sm <?= $viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary bg-white' ?>">
                <i class="bi bi-grid-fill me-1"></i> Kartu
            </a>
            <a href="?search=<?= urlencode($search) ?>&korps=<?= urlencode($korps) ?>&view=table" 
               class="btn btn-sm <?= $viewMode === 'table' ? 'btn-primary' : 'btn-outline-secondary bg-white' ?>">
                <i class="bi bi-list-ul me-1"></i> Tabel
            </a>
            <a href="?search=<?= urlencode($search) ?>&korps=<?= urlencode($korps) ?>&view=map" 
               class="btn btn-sm <?= $viewMode === 'map' ? 'btn-primary' : 'btn-outline-secondary bg-white' ?>">
                <i class="bi bi-geo-alt-fill me-1"></i> Peta
            </a>
        </div>
    </div>
</div>

<!-- 2. Kontrol Saringan & Pencarian (Hierarki Level 2) -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <!-- Pencarian Kata Kunci -->
        <form method="GET" action="dashboard.php" class="mb-3">
            <input type="hidden" name="korps" value="<?= htmlspecialchars($korps) ?>">
            <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">
            
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" name="search" 
                       value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Ketik nama (misal: Arniza), NRP, jabatan, atau nama pangkalan/satuan...">
                <?php if ($search !== ''): ?>
                    <a href="dashboard.php?korps=<?= urlencode($korps) ?>&view=<?= urlencode($viewMode) ?>" class="btn btn-outline-secondary" title="Bersihkan Pencarian">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">Cari</button>
            </div>
        </form>

        <!-- Saringan Korps (Filter Pills) -->
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="small text-muted fw-bold me-1 text-uppercase" style="font-size: 11px;">Saring Korps:</span>
            
            <a href="?search=<?= urlencode($search) ?>&korps=&view=<?= urlencode($viewMode) ?>" 
               class="filter-pill <?= $korps === '' ? 'active' : '' ?>">
                Semua Korps <span class="pill-badge"><?= $totalAll ?></span>
            </a>
            
            <?php foreach ($korpsMeta as $kKey => $kInfo): 
                $countK = $korpsCounts[$kKey] ?? 0;
            ?>
                <a href="?search=<?= urlencode($search) ?>&korps=<?= $kKey ?>&view=<?= urlencode($viewMode) ?>" 
                   class="filter-pill <?= $korps === $kKey ? 'active' : '' ?>">
                    <?= $kKey ?> <span class="pill-badge"><?= $countK ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Ringkasan Hasil Saring -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1">
    <div class="small text-muted">
        Menampilkan <strong><?= count($members) ?></strong> dari total <strong><?= $totalFiltered ?></strong> perwira yang cocok
        <?php if ($korps !== ''): ?>
            dalam <strong><?= $korpsMeta[$korps]['name'] ?></strong>
        <?php endif; ?>
    </div>
    <?php if ($search !== '' || $korps !== ''): ?>
        <a href="dashboard.php?view=<?= urlencode($viewMode) ?>" class="small text-decoration-none">
            <i class="bi bi-arrow-counterclockwise"></i> Reset Semua Saringan
        </a>
    <?php endif; ?>
</div>

<!-- 3. Tampilan Konten Utama (Hierarki Level 3) -->

<?php if ($viewMode === 'map'): ?>
    <!-- Mode 3: PETA SEBARAN LANUD -->
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
        <div id="mapContainer"></div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var map = L.map('mapContainer').setView([-2.5, 118], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var points = <?= json_encode($mapPoints) ?>;
        points.forEach(function(m) {
            if (m.latitude && m.longitude) {
                var popupHtml = `
                    <div style="font-family: sans-serif; font-size: 13px; line-height: 1.4;">
                        <span class="badge bg-secondary mb-1">${m.rank} ${m.korps}</span>
                        <div style="font-weight: bold; color: #0f172a;">${m.name}</div>
                        <div style="color: #475569; font-size: 12px; margin-bottom: 2px;">${m.jabatan || '-'}</div>
                        <div style="color: #0284c7; font-size: 11px;">📍 ${m.unit}</div>
                    </div>
                `;
                L.marker([parseFloat(m.latitude), parseFloat(m.longitude)])
                 .addTo(map)
                 .bindPopup(popupHtml);
            }
        });
    });
    </script>

<?php elseif ($viewMode === 'table'): ?>
    <!-- Mode 2: TABEL RINGKAS -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th style="width: 60px;">Foto</th>
                        <th>Nama Lengkap</th>
                        <th>Korps</th>
                        <th>Satuan & Jabatan</th>
                        <th>NRP</th>
                        <th class="text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($members) > 0): ?>
                        <?php foreach ($members as $i => $m): 
                            $avatarUrl = (!empty($m['photo_path']) && file_exists(__DIR__ . '/../' . $m['photo_path'])) 
                                         ? '../' . htmlspecialchars($m['photo_path']) 
                                         : "https://ui-avatars.com/api/?name=" . urlencode($m['name']) . "&size=80&background=1e3c72&color=fff";
                            $kClass = 'korps-' . ($m['korps'] ?: 'Sus');
                        ?>
                            <tr>
                                <td class="text-center text-muted"><?= $offset + $i + 1 ?></td>
                                <td>
                                    <img src="<?= $avatarUrl ?>" class="rounded-circle border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($m['rank']) ?></small>
                                </td>
                                <td>
                                    <span class="badge-korps <?= $kClass ?>"><?= htmlspecialchars($m['korps']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small"><?= htmlspecialchars($m['jabatan'] ?: 'Perwira Pertama') ?></div>
                                    <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($m['unit']) ?></small>
                                </td>
                                <td><code><?= htmlspecialchars($m['nrp_nia']) ?></code></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#officerModal<?= $m['id'] ?>">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Tidak ada data personel yang cocok dengan pencarian.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- Mode 1 (DEFAULT): KARTU DIREKTORI ANGKATAN (YEARBOOK GRID) -->
    <div class="row g-3 mb-4">
        <?php if (count($members) > 0): ?>
            <?php foreach ($members as $m): 
                $avatarUrl = (!empty($m['photo_path']) && file_exists(__DIR__ . '/../' . $m['photo_path'])) 
                             ? '../' . htmlspecialchars($m['photo_path']) 
                             : "https://ui-avatars.com/api/?name=" . urlencode($m['name']) . "&size=100&background=1e3c72&color=fff";
                $kClass = 'korps-' . ($m['korps'] ?: 'Sus');
            ?>
                <div class="col-sm-6 col-md-4 col-xl-3">
                    <div class="officer-card shadow-sm">
                        <!-- Bagian Atas: Pasfoto & Korps Badge -->
                        <div class="p-3 text-center position-relative pb-2">
                            <div class="d-inline-block position-relative mb-2">
                                <img src="<?= $avatarUrl ?>" class="officer-avatar" alt="<?= htmlspecialchars($m['name']) ?>">
                                <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-primary border border-2 border-white" style="font-size: 9px; padding: 3px 6px;">
                                    LETDA
                                </span>
                            </div>

                            <div class="mb-1">
                                <span class="badge-korps <?= $kClass ?>">
                                    Korps <?= htmlspecialchars($m['korps']) ?>
                                </span>
                            </div>

                            <!-- Nama Lengkap -->
                            <div class="officer-name text-truncate" title="<?= htmlspecialchars($m['name']) ?>">
                                <?= htmlspecialchars($m['name']) ?>
                            </div>
                        </div>

                        <!-- Bagian Tengah: Satuan & Jabatan -->
                        <div class="px-3 pb-3 flex-grow-1 text-center border-top border-light pt-2">
                            <div class="officer-jabatan text-truncate" title="<?= htmlspecialchars($m['jabatan'] ?: 'Perwira Pertama') ?>">
                                <?= htmlspecialchars($m['jabatan'] ?: 'Perwira Pertama') ?>
                            </div>
                            <div class="officer-satuan justify-content-center text-truncate" title="<?= htmlspecialchars($m['unit']) ?>">
                                <i class="bi bi-geo-alt-fill text-danger" style="font-size: 11px;"></i>
                                <?= htmlspecialchars($m['unit']) ?>
                            </div>
                        </div>

                        <!-- Bagian Bawah: Meta NRP & Action -->
                        <div class="card-meta-footer">
                            <span class="text-muted fw-semibold">NRP <?= htmlspecialchars($m['nrp_nia']) ?></span>
                            
                            <div class="d-flex gap-1">
                                <?php if (!empty($m['phone'])): 
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $m['phone']);
                                    if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);
                                    $waText = urlencode("Salam, Yth. " . $m['name'] . ". Dari rekan PAPK AU...");
                                ?>
                                    <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= $waText ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-success btn-xs px-2 py-1 btn-sm" 
                                       title="Hubungi WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <button type="button" 
                                        class="btn btn-outline-primary btn-xs px-2 py-1 btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#officerModal<?= $m['id'] ?>" 
                                        title="Lihat Profil Lengkap">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card shadow-sm border-0 py-5 text-center bg-white">
                    <div class="card-body">
                        <i class="bi bi-person-x fs-1 text-muted opacity-50 mb-2 d-block"></i>
                        <h5 class="fw-bold text-dark">Data Personel Tidak Ditemukan</h5>
                        <p class="text-muted small">Tidak ada perwira yang cocok dengan kata kunci "<?= htmlspecialchars($search) ?>".</p>
                        <a href="dashboard.php" class="btn btn-primary btn-sm">Reset Pencarian</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- 4. Pagination Elegan -->
<?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center my-4">
        <nav aria-label="Navigasi Halaman">
            <ul class="pagination pagination-sm shadow-sm">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&korps=<?= urlencode($korps) ?>&view=<?= urlencode($viewMode) ?>">
                        &laquo; Prev
                    </a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&korps=<?= urlencode($korps) ?>&view=<?= urlencode($viewMode) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&korps=<?= urlencode($korps) ?>&view=<?= urlencode($viewMode) ?>">
                        Next &raquo;
                    </a>
                </li>
            </ul>
        </nav>
    </div>
<?php endif; ?>

<!-- 5. Modal Detail Personel (Bersih & Elegan) -->
<?php foreach ($members as $m): 
    $avatarUrl = (!empty($m['photo_path']) && file_exists(__DIR__ . '/../' . $m['photo_path'])) 
                 ? '../' . htmlspecialchars($m['photo_path']) 
                 : "https://ui-avatars.com/api/?name=" . urlencode($m['name']) . "&size=120&background=1e3c72&color=fff";
    $kClass = 'korps-' . ($m['korps'] ?: 'Sus');
?>
    <div class="modal fade" id="officerModal<?= $m['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center px-4 pt-0 pb-4">
                    <img src="<?= $avatarUrl ?>" class="rounded-circle border border-3 border-primary shadow mb-3" style="width: 96px; height: 96px; object-fit: cover;" alt="Avatar">
                    
                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($m['name']) ?></h5>
                    <div class="d-flex justify-content-center gap-1 mb-3">
                        <span class="badge bg-primary">LETDA</span>
                        <span class="badge-korps <?= $kClass ?>">Korps <?= htmlspecialchars($m['korps']) ?></span>
                        <span class="badge bg-success">AKTIF</span>
                    </div>

                    <div class="card bg-light border-0 text-start p-3 mb-3">
                        <div class="row g-2 small">
                            <div class="col-4 text-muted fw-semibold">NRP / NIA</div>
                            <div class="col-8 fw-bold">: <?= htmlspecialchars($m['nrp_nia']) ?></div>

                            <div class="col-4 text-muted fw-semibold">Jabatan</div>
                            <div class="col-8 fw-bold text-primary">: <?= htmlspecialchars($m['jabatan'] ?: 'Perwira Pertama') ?></div>

                            <div class="col-4 text-muted fw-semibold">Kesatuan / Unit</div>
                            <div class="col-8 fw-semibold">: <?= htmlspecialchars($m['unit']) ?></div>

                            <div class="col-4 text-muted fw-semibold">Pangkalan Dinas</div>
                            <div class="col-8">: <?= htmlspecialchars($m['location_name'] ?: '-') ?></div>

                            <div class="col-4 text-muted fw-semibold">Alamat Domisili</div>
                            <div class="col-8">: <?= htmlspecialchars($m['address'] ?: 'Mess Perwira') ?></div>

                            <div class="col-4 text-muted fw-semibold">Nomor Kontak</div>
                            <div class="col-8">: <?= htmlspecialchars($m['phone'] ?: '-') ?></div>
                        </div>
                    </div>

                    <?php if (!empty($m['phone'])): 
                        $cleanPhone = preg_replace('/[^0-9]/', '', $m['phone']);
                        if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);
                    ?>
                        <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="btn btn-success btn-sm w-100 fw-semibold py-2">
                            <i class="bi bi-whatsapp me-1"></i> Hubungi Rekan via WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
