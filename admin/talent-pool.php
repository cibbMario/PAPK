<?php
// admin/talent-pool.php
// Matriks Kompetensi & Talent Pool Perwira Karier (PAPK TNI AU)
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

// Parameter Filter
$keyword  = trim($_GET['q'] ?? '');
$category = trim($_GET['cat'] ?? '');
$korps    = trim($_GET['korps'] ?? '');

// Statistik Ringkasan
$totalCertified = $pdo->query("SELECT COUNT(DISTINCT member_id) FROM member_competencies WHERE category = 'sertifikasi'")->fetchColumn();
$totalDikmil    = $pdo->query("SELECT COUNT(DISTINCT member_id) FROM member_competencies WHERE category = 'pendidikan_militer'")->fetchColumn();
$totalLanguage  = $pdo->query("SELECT COUNT(DISTINCT member_id) FROM member_competencies WHERE category = 'bahasa'")->fetchColumn();
$totalSpecial   = $pdo->query("SELECT COUNT(DISTINCT member_id) FROM member_competencies WHERE category = 'keahlian_khusus'")->fetchColumn();

// Query Talent Pool
$sql = "SELECT m.id, m.nrp_nia, m.name, m.rank, m.unit, m.phone, m.address, m.photo_path, m.status,
               GROUP_CONCAT(CONCAT_WS('::', mc.id, mc.category, mc.title, COALESCE(mc.institution,''), COALESCE(mc.year,''), COALESCE(mc.certificate_no,'')) SEPARATOR '||') as comp_list
        FROM members m
        LEFT JOIN member_competencies mc ON m.id = mc.member_id
        WHERE 1=1 ";
$params = [];

if ($keyword !== '') {
    $sql .= "AND (m.name LIKE :kw OR mc.title LIKE :kw OR mc.institution LIKE :kw OR mc.description LIKE :kw OR m.unit LIKE :kw) ";
    $params[':kw'] = "%$keyword%";
}

if ($category !== '') {
    $sql .= "AND mc.category = :cat ";
    $params[':cat'] = $category;
}

if ($korps !== '') {
    $sql .= "AND m.name LIKE :korps ";
    $params[':korps'] = "% $korps %";
}

$sql .= "GROUP BY m.id ORDER BY m.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$talents = $stmt->fetchAll();

// Mapping Kategori Kompetensi
$catMeta = [
    'profesi' => ['label' => 'Profesi & Keilmuan', 'badge' => 'bg-primary', 'icon' => 'bi-briefcase-fill'],
    'pendidikan_militer' => ['label' => 'Pendidikan Militer', 'badge' => 'bg-success', 'icon' => 'bi-shield-check'],
    'sertifikasi' => ['label' => 'Sertifikasi Nasional/Intl', 'badge' => 'bg-purple', 'icon' => 'bi-award-fill'],
    'bahasa' => ['label' => 'Bahasa Asing', 'badge' => 'bg-warning text-dark', 'icon' => 'bi-translate'],
    'keahlian_khusus' => ['label' => 'Keahlian Khusus / Brevet', 'badge' => 'bg-danger', 'icon' => 'bi-star-fill'],
];
?>

<style>
.bg-purple { background-color: #6f42c1 !important; color: white !important; }
.talent-card { transition: transform 0.2s, box-shadow 0.2s; border-radius: 12px; }
.talent-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Matriks Kompetensi & Talent Pool</h1>
        <p class="text-muted small mb-0">Database keahlian profesi, sertifikasi, dan pendidikan militer Perwira PK TNI AU.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-printer"></i> Cetak Laporan</button>
        <a href="talent-pool.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-clockwise"></i> Reset Filter</a>
    </div>
</div>

<!-- Statistik Matriks -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-primary">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Tersertifikasi Profesi</div>
                        <div class="h3 mb-0 fw-bold text-primary mt-1"><?= $totalCertified ?> Perwira</div>
                    </div>
                    <div class="fs-1 text-primary opacity-25"><i class="bi bi-award-fill"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Kualifikasi Dikmil/Kursus</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1"><?= $totalDikmil ?> Perwira</div>
                    </div>
                    <div class="fs-1 text-success opacity-25"><i class="bi bi-shield-check"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Kemahiran Bahasa Asing</div>
                        <div class="h3 mb-0 fw-bold text-warning text-dark mt-1"><?= $totalLanguage ?> Perwira</div>
                    </div>
                    <div class="fs-1 text-warning opacity-25"><i class="bi bi-translate"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-danger">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Kualifikasi Khusus / Brevet</div>
                        <div class="h3 mb-0 fw-bold text-danger mt-1"><?= $totalSpecial ?> Perwira</div>
                    </div>
                    <div class="fs-1 text-danger opacity-25"><i class="bi bi-star-fill"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panel Pencarian & Filter Cerdas -->
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body p-3">
        <form method="GET" action="talent-pool.php" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari keahlian: Dokter, Cyber, Radar, Hukum...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="cat">
                    <option value="">-- Semua Kategori Kompetensi --</option>
                    <?php foreach ($catMeta as $key => $meta): ?>
                        <option value="<?= $key ?>" <?= $category === $key ? 'selected' : '' ?>><?= $meta['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="korps">
                    <option value="">-- Semua Korps --</option>
                    <option value="Sus" <?= $korps === 'Sus' ? 'selected' : '' ?>>Korps Khusus (Sus)</option>
                    <option value="Lek" <?= $korps === 'Lek' ? 'selected' : '' ?>>Korps Elektronika (Lek)</option>
                    <option value="Tek" <?= $korps === 'Tek' ? 'selected' : '' ?>>Korps Teknik (Tek)</option>
                    <option value="Kes" <?= $korps === 'Kes' ? 'selected' : '' ?>>Korps Kesehatan (Kes)</option>
                    <option value="Kal" <?= $korps === 'Kal' ? 'selected' : '' ?>>Korps Pembekalan (Kal)</option>
                    <option value="Adm" <?= $korps === 'Adm' ? 'selected' : '' ?>>Korps Administrasi (Adm)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-1"></i> Saring</button>
                <?php if ($keyword !== '' || $category !== '' || $korps !== ''): ?>
                    <a href="talent-pool.php" class="btn btn-outline-secondary" title="Hapus Filter"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Hasil Pencarian Talent Pool -->
<div class="row g-4 mb-5">
    <?php if (count($talents) > 0): ?>
        <?php foreach ($talents as $t): 
            $avatarImg = (!empty($t['photo_path']) && file_exists(__DIR__ . '/../' . $t['photo_path'])) 
                         ? '../' . htmlspecialchars($t['photo_path']) 
                         : "https://ui-avatars.com/api/?name=" . urlencode($t['name']) . "&size=80&background=1e3c72&color=fff";
            
            // Parse daftar kompetensi
            $comps = [];
            if (!empty($t['comp_list'])) {
                $rawItems = explode('||', $t['comp_list']);
                foreach ($rawItems as $item) {
                    $parts = explode('::', $item);
                    if (count($parts) >= 3) {
                        $comps[] = [
                            'id' => $parts[0],
                            'category' => $parts[1],
                            'title' => $parts[2],
                            'institution' => $parts[3] ?? '',
                            'year' => $parts[4] ?? '',
                            'cert_no' => $parts[5] ?? ''
                        ];
                    }
                }
            }
        ?>
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100 shadow-sm border-0 talent-card">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= $avatarImg ?>" class="rounded-circle border border-2 border-primary me-3 shadow-sm" style="width: 58px; height: 58px; object-fit: cover;" alt="Avatar">
                            <div>
                                <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($t['name']) ?></h6>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="badge bg-secondary" style="font-size: 11px;"><?= htmlspecialchars($t['rank']) ?></span>
                                    <small class="text-muted">NRP: <?= htmlspecialchars($t['nrp_nia']) ?></small>
                                </div>
                                <small class="text-primary fw-semibold"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($t['unit']) ?></small>
                            </div>
                        </div>

                        <hr class="my-2 opacity-25">

                        <div class="mb-3 flex-grow-1">
                            <small class="text-muted fw-bold d-block mb-2 text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Kompetensi & Portofolio:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if (count($comps) > 0): ?>
                                    <?php foreach ($comps as $c): 
                                        $meta = $catMeta[$c['category']] ?? ['label' => 'Lainnya', 'badge' => 'bg-secondary', 'icon' => 'bi-tag'];
                                    ?>
                                        <span class="badge <?= $meta['badge'] ?> fw-normal d-inline-flex align-items-center px-2 py-1" style="font-size: 11px;">
                                            <i class="bi <?= $meta['icon'] ?> me-1"></i> <?= htmlspecialchars($c['title']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">Belum ada portofolio tercatat.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="d-flex gap-2 pt-2 border-top">
                            <button class="btn btn-outline-primary btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#modalDetailTalent<?= $t['id'] ?>">
                                <i class="bi bi-eye"></i> Detail Portofolio
                            </button>
                            <?php if (!empty($t['phone'])): 
                                $cleanPhone = preg_replace('/[^0-9]/', '', $t['phone']);
                                if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);
                                $waText = urlencode("Salam, Yth. " . $t['name'] . ". Terkait verifikasi penugasan dan keahlian profesi melalui portal PAPK TNI AU...");
                            ?>
                                <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= $waText ?>" target="_blank" class="btn btn-outline-success btn-sm" title="Hubungi WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Detail Portofolio Lengkap -->
            <div class="modal fade" id="modalDetailTalent<?= $t['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold"><i class="bi bi-person-badge text-primary me-2"></i>Portofolio Lengkap Personel</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="d-flex align-items-center mb-4">
                                <img src="<?= $avatarImg ?>" class="rounded-circle border border-3 border-primary me-3" style="width: 70px; height: 70px; object-fit: cover;">
                                <div>
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($t['name']) ?></h5>
                                    <p class="mb-0 text-muted small">Pangkat: <strong><?= htmlspecialchars($t['rank']) ?></strong> | NRP: <strong><?= htmlspecialchars($t['nrp_nia']) ?></strong> | Kesatuan: <strong><?= htmlspecialchars($t['unit']) ?></strong></p>
                                    <p class="mb-0 text-muted small"><i class="bi bi-telephone"></i> <?= htmlspecialchars($t['phone'] ?: '-') ?> | <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($t['address'] ?: '-') ?></p>
                                </div>
                            </div>

                            <h6 class="fw-bold border-bottom pb-2 mb-3">Daftar Sertifikasi, Keahlian & Pendidikan Kedinasan:</h6>
                            <?php if (count($comps) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 130px;">Kategori</th>
                                                <th>Nama Sertifikat / Keahlian</th>
                                                <th>Lembaga Penerbit</th>
                                                <th style="width: 80px;" class="text-center">Tahun</th>
                                                <th>No. Sertifikat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($comps as $c): 
                                                $meta = $catMeta[$c['category']] ?? ['label' => 'Lainnya', 'badge' => 'bg-secondary', 'icon' => 'bi-tag'];
                                            ?>
                                                <tr>
                                                    <td><span class="badge <?= $meta['badge'] ?>"><i class="bi <?= $meta['icon'] ?> me-1"></i><?= $meta['label'] ?></span></td>
                                                    <td class="fw-semibold"><?= htmlspecialchars($c['title']) ?></td>
                                                    <td><?= htmlspecialchars($c['institution'] ?: '-') ?></td>
                                                    <td class="text-center"><?= htmlspecialchars($c['year'] ?: '-') ?></td>
                                                    <td><code><?= htmlspecialchars($c['cert_no'] ?: '-') ?></code></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light text-center py-4 text-muted">Belum ada riwayat kompetensi yang dicatatkan untuk perwira ini.</div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card shadow-sm border-0 py-5 text-center">
                <div class="card-body">
                    <i class="bi bi-search fs-1 text-muted opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-muted">Tidak Ditemukan Personel Sesuai Kriteria</h5>
                    <p class="text-muted small">Coba sesuaikan kata kunci pencarian, kategori kompetensi, atau pilihan korps.</p>
                    <a href="talent-pool.php" class="btn btn-primary btn-sm"><i class="bi bi-arrow-clockwise"></i> Reset Pencarian</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
