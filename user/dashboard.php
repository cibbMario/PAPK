<?php
// user/dashboard.php
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 1. Ambil data anggota untuk marker peta
$stmtMap = $pdo->query("SELECT id, nrp_nia, name, `rank`, unit, location_name, latitude, longitude, phone, status 
                        FROM members 
                        WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND status = 'aktif'");
$locations = $stmtMap->fetchAll(PDO::FETCH_ASSOC);

// 2. Pagination & Pencarian data anggota untuk tabel di bawah peta
$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$filterRank = trim($_GET['rank'] ?? '');
$searchSql = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $searchSql .= " AND (name LIKE :kw OR nrp_nia LIKE :kw OR unit LIKE :kw OR location_name LIKE :kw)";
    $params[':kw'] = "%$search%";
}
if ($filterRank !== '') {
    $searchSql .= " AND `rank` = :rank";
    $params[':rank'] = $filterRank;
}

// Total rows
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM members $searchSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Ambil data anggota halaman ini
$dataStmt = $pdo->prepare("SELECT * FROM members $searchSql ORDER BY id ASC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) {
    $dataStmt->bindValue($k, $v);
}
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$membersList = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<style>
    #indonesiaMap {
        height: 520px;
        width: 100%;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border: 1px solid #dee2e6;
        z-index: 1;
    }
    .custom-popup .leaflet-popup-content-wrapper {
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Dashboard Anggota PAPK TNI AU</h1>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-primary fs-6 p-2"><i class="bi bi-person-check me-1"></i> Halo, <?= htmlspecialchars($username) ?></span>
    </div>
</div>

<!-- 1. Peta Sebaran Wilayah Dinas -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Peta Sebaran Wilayah Dinas</h5>
        <span class="badge bg-light text-muted border">Ina-Geoportal Basemap</span>
    </div>
    <div class="card-body p-3">
        <div id="indonesiaMap"></div>
    </div>
</div>

<!-- 2. Tabel Direktori Data Anggota PAPK -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <div class="row align-items-center g-2">
            <div class="col-md-5">
                <h5 class="mb-0 fw-bold"><i class="bi bi-people-fill text-primary me-2"></i>Direktori Data Rekan Anggota PAPK</h5>
                <small class="text-muted">Cari rekan satu angkatan, satuan, atau pangkalan dinas di seluruh Indonesia.</small>
            </div>
            <div class="col-md-7">
                <form class="d-flex justify-content-md-end gap-2" method="get" action="dashboard.php">
                    <select class="form-select form-select-sm" name="rank" style="max-width: 140px;">
                        <option value="">Semua Pangkat</option>
                        <option value="Letda" <?= $filterRank === 'Letda' ? 'selected' : '' ?>>Letda</option>
                        <option value="Lettu" <?= $filterRank === 'Lettu' ? 'selected' : '' ?>>Lettu</option>
                        <option value="Kapten" <?= $filterRank === 'Kapten' ? 'selected' : '' ?>>Kapten</option>
                        <option value="Mayor" <?= $filterRank === 'Mayor' ? 'selected' : '' ?>>Mayor</option>
                        <option value="Letkol" <?= $filterRank === 'Letkol' ? 'selected' : '' ?>>Letkol</option>
                        <option value="Kolonel" <?= $filterRank === 'Kolonel' ? 'selected' : '' ?>>Kolonel</option>
                    </select>
                    <input class="form-control form-control-sm" style="max-width: 240px;" type="search" name="search" 
                           placeholder="Cari Nama / NRP / Satuan..." value="<?= htmlspecialchars($search) ?>" />
                    <button class="btn btn-sm btn-primary" type="submit">Cari</button>
                    <?php if ($search !== '' || $filterRank !== ''): ?>
                        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 5%;">#</th>
                        <th style="width: 14%;">NRP / NIA</th>
                        <th style="width: 25%;">Nama Lengkap & Gelar</th>
                        <th style="width: 12%;">Pangkat</th>
                        <th style="width: 24%;">Satuan & Pangkalan Dinas</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 10%;" class="pe-3 text-center">Kontak</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($membersList)): ?>
                        <?php foreach ($membersList as $idx => $m): ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $offset + $idx + 1 ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($m['nrp_nia']) ?></span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php 
                                        $avatarImg = (!empty($m['photo_path']) && file_exists(__DIR__ . '/../' . $m['photo_path'])) 
                                                     ? '../' . htmlspecialchars($m['photo_path']) 
                                                     : "https://ui-avatars.com/api/?name=" . urlencode($m['name']) . "&size=40&background=0d6efd&color=fff";
                                    ?>
                                    <img src="<?= $avatarImg ?>" class="rounded-circle me-2 border shadow-sm" style="width: 38px; height: 38px; object-fit: cover;" alt="Avatar">
                                    <div>
                                        <strong><?= htmlspecialchars($m['name']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($m['rank']) ?></span></td>
                            <td>
                                <div><strong><?= htmlspecialchars($m['unit']) ?></strong></div>
                                <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($m['location_name'] ?: '-') ?></small>
                            </td>
                            <td>
                                <?php if ($m['status'] === 'aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php elseif ($m['status'] === 'pensiun'): ?>
                                    <span class="badge bg-secondary">Pensiun</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Non-aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-3 text-center">
                                <?php if (!empty($m['phone'])): ?>
                                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $m['phone'])) ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Hubungi via WA / Telepon">
                                        <i class="bi bi-whatsapp"></i> Kontak
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ada data anggota yang cocok dengan kata kunci pencarian.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white py-3">
        <nav class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Menampilkan halaman <?= $page ?> dari <?= $totalPages ?> (Total: <?= $totalRows ?> anggota)</small>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&rank=<?= urlencode($filterRank) ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&rank=<?= urlencode($filterRank) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&rank=<?= urlencode($filterRank) ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    // Koordinat tengah Indonesia
    const indonesiaCenter = [-2.0, 118.0];
    const map = L.map('indonesiaMap').setView(indonesiaCenter, 5);

    // Basemap Topografi Biru Cerah (Ina-Geoportal style)
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 18,
        attribution: '&copy; Esri, OpenStreetMap contributors'
    }).addTo(map);

    // Data lokasi personel dari PHP
    const locationsData = <?= json_encode($locations) ?>;

    // Custom Icon Marker Pesawat TNI AU
    const tniIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -30]
    });

    locationsData.forEach(function(member) {
        if (member.latitude && member.longitude) {
            const popupContent = `
                <div class="custom-popup" style="min-width: 220px; font-size: 13px;">
                    <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&size=36&background=0d6efd&color=fff" class="rounded-circle me-2" alt="Foto">
                        <div>
                            <strong class="text-primary d-block">${member.name}</strong>
                            <span class="badge bg-secondary" style="font-size: 10px;">${member.rank}</span>
                        </div>
                    </div>
                    <div class="text-muted" style="line-height: 1.5;">
                        <div><strong>NRP:</strong> ${member.nrp_nia}</div>
                        <div><strong>Satuan:</strong> ${member.unit}</div>
                        <div><strong>Penempatan:</strong> <span class="badge bg-primary text-white">${member.location_name || '-'}</span></div>
                        <div class="mt-1"><strong>Kontak:</strong> <a href="tel:${member.phone || ''}" class="text-primary text-decoration-none">${member.phone || '-'}</a></div>
                    </div>
                </div>
            `;

            L.marker([parseFloat(member.latitude), parseFloat(member.longitude)], { icon: tniIcon })
                .addTo(map)
                .bindPopup(popupContent);
        }
    });
</script>
