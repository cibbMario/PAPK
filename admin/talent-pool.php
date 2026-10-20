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
    'profesi' => ['label' => 'Profesi & Keilmuan', 'badge' => 'bg-dark text-white'],
    'pendidikan_militer' => ['label' => 'Pendidikan Militer', 'badge' => 'bg-dark text-white'],
    'sertifikasi' => ['label' => 'Sertifikasi Nasional/Intl', 'badge' => 'bg-light text-dark border'],
    'bahasa' => ['label' => 'Bahasa Asing', 'badge' => 'bg-light text-dark border'],
    'keahlian_khusus' => ['label' => 'Keahlian Khusus / Brevet', 'badge' => 'bg-dark text-white'],
];
?>

<style>
.talent-card { transition: transform 0.2s, box-shadow 0.2s; border-radius: 12px; }
.talent-card:hover { transform: translateY(-3px); box-shadow: 0 8px 18px rgba(0,0,0,0.06) !important; }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2 font-serif fw-bold mb-1">Matriks Kompetensi & Talent Pool</h1>
        <p class="text-muted small mb-0">Database keahlian profesi, sertifikasi, dan pendidikan militer Perwira PK TNI AU.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark me-2">Cetak Laporan</button>
        <a href="talent-pool.php" class="btn btn-sm btn-dark">Reset Filter</a>
    </div>
</div>

<!-- Hasil Pencarian Talent Pool -->
<div class="row g-4 mb-5">
    <?php if (count($talents) > 0): ?>
        <?php foreach ($talents as $t): 
            $avatarImg = (!empty($t['photo_path']) && file_exists(__DIR__ . '/../' . $t['photo_path'])) 
                         ? '../' . htmlspecialchars($t['photo_path']) 
                         : "https://ui-avatars.com/api/?name=" . urlencode($t['name']) . "&size=80&background=0f172a&color=fff";
            
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
                <div class="card h-100 border talent-card">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= $avatarImg ?>" class="rounded-circle border border-dark me-3" style="width: 58px; height: 58px; object-fit: cover;" alt="Avatar">
                            <div>
                                <h6 class="mb-0 fw-bold font-serif text-dark"><?= htmlspecialchars($t['name']) ?></h6>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="badge bg-light text-dark border" style="font-size: 11px;"><?= htmlspecialchars($t['rank']) ?></span>
                                    <small class="text-muted">NRP: <?= htmlspecialchars($t['nrp_nia']) ?></small>
                                </div>
                                <small class="text-dark fw-semibold"><?= htmlspecialchars($t['unit']) ?></small>
                            </div>
                        </div>

                        <hr class="my-2 opacity-25">

                        <div class="mb-3 flex-grow-1">
                            <small class="text-muted fw-bold d-block mb-2 text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Kompetensi & Portofolio:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if (count($comps) > 0): ?>
                                    <?php foreach ($comps as $c): 
                                        $meta = $catMeta[$c['category']] ?? ['label' => 'Lainnya', 'badge' => 'bg-light text-dark border'];
                                    ?>
                                        <span class="badge <?= $meta['badge'] ?> fw-normal d-inline-flex align-items-center px-2 py-1" style="font-size: 11px;">
                                            <?= htmlspecialchars($c['title']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">Belum ada portofolio tercatat.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="d-flex gap-2 pt-2 border-top">
                            <button class="btn btn-outline-dark btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#modalDetailTalent<?= $t['id'] ?>">
                                Detail Portofolio
                            </button>
                            <?php if (!empty($t['phone'])): 
                                $cleanPhone = preg_replace('/[^0-9]/', '', $t['phone']);
                                if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);
                                $waText = urlencode("Salam, Yth. " . $t['name'] . ". Terkait verifikasi penugasan dan keahlian profesi melalui portal PAPK TNI AU...");
                            ?>
                                <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= $waText ?>" target="_blank" class="btn btn-outline-dark btn-sm" title="Hubungi WhatsApp">
                                    WhatsApp
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Detail Portofolio Lengkap -->
            <div class="modal fade" id="modalDetailTalent<?= $t['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold font-serif">Portofolio Lengkap Personel</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="d-flex align-items-center mb-4">
                                <img src="<?= $avatarImg ?>" class="rounded-circle border border-dark me-3" style="width: 70px; height: 70px; object-fit: cover;">
                                <div>
                                    <h5 class="fw-bold font-serif mb-1"><?= htmlspecialchars($t['name']) ?></h5>
                                    <p class="mb-0 text-muted small">Pangkat: <strong><?= htmlspecialchars($t['rank']) ?></strong> | NRP: <strong><?= htmlspecialchars($t['nrp_nia']) ?></strong> | Kesatuan: <strong><?= htmlspecialchars($t['unit']) ?></strong></p>
                                    <p class="mb-0 text-muted small">Telp: <?= htmlspecialchars($t['phone'] ?: '-') ?> | Alamat: <?= htmlspecialchars($t['address'] ?: '-') ?></p>
                                </div>
                            </div>

                            <h6 class="fw-bold font-serif border-bottom pb-2 mb-3">Daftar Sertifikasi, Keahlian & Pendidikan Kedinasan:</h6>
                            <?php if (count($comps) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 140px;">Kategori</th>
                                                <th>Nama Sertifikat / Keahlian</th>
                                                <th>Lembaga Penerbit</th>
                                                <th style="width: 80px;" class="text-center">Tahun</th>
                                                <th>No. Sertifikat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($comps as $c): 
                                                $meta = $catMeta[$c['category']] ?? ['label' => 'Lainnya', 'badge' => 'bg-light text-dark border'];
                                            ?>
                                                <tr>
                                                    <td><span class="badge <?= $meta['badge'] ?>"><?= $meta['label'] ?></span></td>
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
                            <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card border py-5 text-center bg-white" style="border-radius: 12px;">
                <div class="card-body">
                    <h5 class="fw-bold text-dark font-serif mb-1">Tidak Ditemukan Personel Sesuai Kriteria</h5>
                    <p class="text-muted small mb-3">Coba sesuaikan kata kunci pencarian, kategori kompetensi, atau pilihan korps.</p>
                    <a href="talent-pool.php" class="btn btn-dark btn-sm">Reset Pencarian</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
