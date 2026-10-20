<?php
// user/kompetensi.php
// Kelola Matriks Kompetensi & Portofolio Perwira Mandiri
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

$userId = $_SESSION['user_id'];

// Ambil data anggota yang terhubung ke user ini
$stmtUser = $pdo->prepare("SELECT u.*, m.id as mid, m.name, m.rank, m.unit, m.nrp_nia, m.phone 
                          FROM users u 
                          LEFT JOIN members m ON u.member_id = m.id 
                          WHERE u.id = :id");
$stmtUser->execute([':id' => $userId]);
$userData = $stmtUser->fetch();
$memberId = $userData['mid'] ?? null;

if (!$memberId) {
    echo "<div class='alert alert-warning m-4'>Akun Anda belum terhubung dengan data keanggotaan perwira aktif. Silakan hubungi Administrator.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Handle Tambah / Edit Kompetensi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $category = $_POST['category'] ?? 'sertifikasi';
    $title    = trim($_POST['title'] ?? '');
    $inst     = trim($_POST['institution'] ?? '');
    $year     = trim($_POST['year'] ?? '');
    $certNo   = trim($_POST['certificate_no'] ?? '');
    $desc     = trim($_POST['description'] ?? '');

    $allowedCategories = ['profesi', 'pendidikan_militer', 'sertifikasi', 'bahasa', 'keahlian_khusus'];
    if (!in_array($category, $allowedCategories)) {
        $category = 'sertifikasi';
    }

    if (!empty($title)) {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO member_competencies (member_id, category, title, institution, `year`, certificate_no, description) 
                                   VALUES (:mid, :cat, :title, :inst, :year, :cert, :desc)");
            $stmt->execute([
                ':mid'   => $memberId,
                ':cat'   => $category,
                ':title' => $title,
                ':inst'  => $inst,
                ':year'  => $year,
                ':cert'  => $certNo,
                ':desc'  => $desc
            ]);
            setFlash('Kompetensi / Sertifikasi baru berhasil ditambahkan ke portofolio Anda!', 'success');
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE member_competencies 
                                   SET category = :cat, title = :title, institution = :inst, `year` = :year, certificate_no = :cert, description = :desc 
                                   WHERE id = :id AND member_id = :mid");
            $stmt->execute([
                ':cat'   => $category,
                ':title' => $title,
                ':inst'  => $inst,
                ':year'  => $year,
                ':cert'  => $certNo,
                ':desc'  => $desc,
                ':id'    => $id,
                ':mid'   => $memberId
            ]);
            setFlash('Data kompetensi berhasil diperbarui!', 'success');
        }
    }
    header('Location: kompetensi.php');
    exit;
}

// Handle Hapus Kompetensi
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $del = $pdo->prepare("DELETE FROM member_competencies WHERE id = :id AND member_id = :mid");
    $del->execute([':id' => $id, ':mid' => $memberId]);
    setFlash('Kompetensi berhasil dihapus dari portofolio.', 'info');
    header('Location: kompetensi.php');
    exit;
}

// Ambil Riwayat Kompetensi Anggota Ini
$stmtComp = $pdo->prepare("SELECT * FROM member_competencies WHERE member_id = :mid ORDER BY year DESC, id DESC");
$stmtComp->execute([':mid' => $memberId]);
$competencies = $stmtComp->fetchAll();

$catMeta = [
    'profesi' => ['label' => 'Profesi & Keilmuan', 'badge' => 'bg-dark text-white'],
    'pendidikan_militer' => ['label' => 'Pendidikan Militer', 'badge' => 'bg-dark text-white'],
    'sertifikasi' => ['label' => 'Sertifikasi Nasional/Intl', 'badge' => 'bg-light text-dark border'],
    'bahasa' => ['label' => 'Kemampuan Bahasa Asing', 'badge' => 'bg-light text-dark border'],
    'keahlian_khusus' => ['label' => 'Keahlian Khusus / Brevet', 'badge' => 'bg-dark text-white'],
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2 mb-1 font-serif">Portofolio & Kompetensi Personel</h1>
        <p class="text-muted small mb-0">Kelola riwayat sertifikasi profesi, pendidikan militer, dan keahlian khusus Anda.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#compModal" onclick="prepareAdd()">
            Tambah Portofolio Baru
        </button>
    </div>
</div>

<!-- Header Ringkasan Profil Perwira -->
<div class="card border mb-4 bg-white" style="border-radius: 12px;">
    <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h6 class="mb-1 fw-bold font-serif fs-5"><?= htmlspecialchars($userData['name']) ?></h6>
            <small class="text-muted">Pangkat: <?= htmlspecialchars($userData['rank']) ?> | NRP: <?= htmlspecialchars($userData['nrp_nia']) ?> | Satuan: <?= htmlspecialchars($userData['unit']) ?></small>
        </div>
        <div>
            <span class="badge bg-dark text-white fs-6 px-3 py-2"><?= count($competencies) ?> Portofolio Tercatat</span>
        </div>
    </div>
</div>

<!-- Daftar Kompetensi -->
<div class="card border mb-4" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold font-serif">Daftar Sertifikasi & Keahlian Khusus</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Kategori</th>
                        <th>Nama Sertifikat / Keahlian</th>
                        <th>Lembaga Penyelenggara</th>
                        <th class="text-center" style="width: 80px;">Tahun</th>
                        <th>No. Sertifikat</th>
                        <th style="width: 140px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($competencies) > 0): ?>
                        <?php foreach ($competencies as $i => $c): 
                            $meta = $catMeta[$c['category']] ?? ['label' => 'Lainnya', 'badge' => 'bg-light text-dark border'];
                        ?>
                            <tr>
                                <td class="text-center text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <span class="badge <?= $meta['badge'] ?>">
                                        <?= $meta['label'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($c['title']) ?></div>
                                    <?php if (!empty($c['description'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($c['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($c['institution'] ?: '-') ?></td>
                                <td class="text-center"><?= htmlspecialchars($c['year'] ?: '-') ?></td>
                                <td><code><?= htmlspecialchars($c['certificate_no'] ?: '-') ?></code></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-dark me-1" 
                                            onclick="prepareEdit(<?= htmlspecialchars(json_encode($c)) ?>)"
                                            data-bs-toggle="modal" data-bs-target="#compModal" title="Edit">
                                        Edit
                                    </button>
                                    <a href="kompetensi.php?action=delete&id=<?= $c['id'] ?>" 
                                       onclick="return confirm('Hapus kompetensi ini dari portofolio Anda?');" 
                                       class="btn btn-sm btn-outline-dark" title="Hapus">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Belum ada data kompetensi atau sertifikasi yang ditambahkan.<br>
                                <button type="button" class="btn btn-dark btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#compModal" onclick="prepareAdd()">
                                    Tambah Sekarang
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Kompetensi -->
<div class="modal fade" id="compModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="kompetensi.php">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="modalId" value="">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Tambah Kompetensi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Kategori Bidang *</label>
                            <select class="form-select" name="category" id="modalCategory" required>
                                <option value="profesi">Profesi & Spesialisasi Keilmuan Sarjana</option>
                                <option value="pendidikan_militer">Pendidikan Militer / Diksargol / Kursus Kedinasan</option>
                                <option value="sertifikasi">Sertifikasi Keahlian Nasional / Internasional</option>
                                <option value="bahasa">Kemahiran Bahasa Asing (ALCPT / TOEFL / IELTS / dll)</option>
                                <option value="keahlian_khusus">Keahlian Khusus / Brevet / Kualifikasi Tempur</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tahun Kelulusan / Perolehan</label>
                            <input type="text" class="form-control" name="year" id="modalYear" placeholder="Contoh: 2024">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Sertifikat / Kompetensi / Kualifikasi *</label>
                        <input type="text" class="form-control" name="title" id="modalNameInput" placeholder="Contoh: Certified Ethical Hacker (CEH) / Brevet Para Dasar" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Lembaga Penyelenggara / Penerbit</label>
                            <input type="text" class="form-control" name="institution" id="modalInst" placeholder="Contoh: BSSN / Wingdik 500 / Kemhan / EC-Council">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Nomor Registrasi / Sertifikat</label>
                            <input type="text" class="form-control" name="certificate_no" id="modalCertNo" placeholder="Contoh: CERT-2024-9908">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi / Ruang Lingkup Keahlian</label>
                        <textarea class="form-control" name="description" id="modalDesc" rows="3" placeholder="Jelaskan ringkas keahlian, materi kursus, atau nilai/skor yang diperoleh..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Simpan ke Portofolio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prepareAdd() {
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalId').value = '';
    document.getElementById('modalTitle').textContent = 'Tambah Kompetensi Baru';
    document.getElementById('modalCategory').value = 'profesi';
    document.getElementById('modalNameInput').value = '';
    document.getElementById('modalInst').value = '';
    document.getElementById('modalYear').value = new Date().getFullYear();
    document.getElementById('modalCertNo').value = '';
    document.getElementById('modalDesc').value = '';
    document.getElementById('modalSubmitBtn').textContent = 'Simpan ke Portofolio';
}

function prepareEdit(c) {
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalId').value = c.id;
    document.getElementById('modalTitle').textContent = 'Edit Data Kompetensi';
    document.getElementById('modalCategory').value = c.category;
    document.getElementById('modalNameInput').value = c.title;
    document.getElementById('modalInst').value = c.institution || '';
    document.getElementById('modalYear').value = c.year || '';
    document.getElementById('modalCertNo').value = c.certificate_no || '';
    document.getElementById('modalDesc').value = c.description || '';
    document.getElementById('modalSubmitBtn').textContent = 'Simpan Perubahan';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
