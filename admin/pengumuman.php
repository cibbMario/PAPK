<?php
// admin/pengumuman.php
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$adminId = $_SESSION['user_id'];

// Handle Tambah / Edit Pengumuman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $title    = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? 'pengumuman';
    $content  = trim($_POST['content'] ?? '');

    // Handle Upload Gambar (Infografis/Banner)
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExts)) {
            $filename = 'ann_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $imagePath = 'uploads/' . $filename;
            }
        }
    }

    if (!empty($title) && !empty($content)) {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, category, content, image_path, created_by) 
                                  VALUES (:title, :cat, :content, :img, :created_by)");
            $stmt->execute([
                ':title'      => $title,
                ':cat'        => $category,
                ':content'    => $content,
                ':img'        => $imagePath,
                ':created_by' => $adminId
            ]);
            setFlash('Konten / Pengumuman berhasil diterbitkan!', 'success');
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            if ($imagePath) {
                $stmt = $pdo->prepare("UPDATE announcements SET title = :title, category = :cat, content = :content, image_path = :img WHERE id = :id");
                $stmt->execute([':title' => $title, ':cat' => $category, ':content' => $content, ':img' => $imagePath, ':id' => $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE announcements SET title = :title, category = :cat, content = :content WHERE id = :id");
                $stmt->execute([':title' => $title, ':cat' => $category, ':content' => $content, ':id' => $id]);
            }
            setFlash('Pengumuman berhasil diperbarui!', 'success');
        }
    }
    header('Location: pengumuman.php');
    exit;
}

// Handle Hapus Pengumuman
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $del = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
    $del->execute([':id' => $id]);
    setFlash('Pengumuman telah dihapus.', 'danger');
    header('Location: pengumuman.php');
    exit;
}

// Ambil semua pengumuman
$stmt = $pdo->query("SELECT a.*, u.username FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.id DESC");
$announcements = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2">Kelola Info, Perwira Baru & Infografis</h1>
        <p class="text-muted small mb-0">Publikasikan pengumuman dinas, informasi perwira baru lulusan kawah candradimuka, dan visual infografis.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#announcementModal" data-action="add">
        + Buat Informasi Baru
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 5%;">#</th>
                        <th style="width: 25%;">Judul Konten</th>
                        <th style="width: 15%;">Kategori</th>
                        <th style="width: 35%;">Ringkasan Isi</th>
                        <th style="width: 20%;" class="pe-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($announcements)): ?>
                        <?php foreach ($announcements as $idx => $ann): ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($ann['title']) ?></strong><br>
                                    <small class="text-muted"><?= date('d M Y H:i', strtotime($ann['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($ann['category'] === 'perwira_baru'): ?>
                                        <span class="badge bg-success">Info Perwira Baru</span>
                                    <?php elseif ($ann['category'] === 'infografis'): ?>
                                        <span class="badge bg-warning text-dark">Infografis</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-white">Edaran Kedinasan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary small">
                                    <?= nl2br(htmlspecialchars(mb_strimwidth($ann['content'], 0, 120, '...'))) ?>
                                </td>
                                <td class="pe-3 text-center">
                                    <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#announcementModal" 
                                            data-action="edit" data-id="<?= $ann['id'] ?>" data-title="<?= htmlspecialchars($ann['title']) ?>" 
                                            data-category="<?= $ann['category'] ?>"
                                            data-content="<?= htmlspecialchars($ann['content']) ?>">
                                        Edit
                                    </button>
                                    <a href="?action=delete&id=<?= $ann['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus informasi ini?')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada pengumuman yang dibuat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Pengumuman -->
<div class="modal fade" id="announcementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="pengumuman.php" enctype="multipart/form-data">
                <input type="hidden" name="action" id="modalAnnAction" value="add">
                <input type="hidden" name="id" id="modalAnnId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAnnTitle">Buat Informasi / Pengumuman Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Informasi *</label>
                        <input type="text" class="form-control" name="title" id="modalTitleInput" placeholder="Contoh: Info Penerimaan & Penempatan Perwira Remaja PAPK" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori Konten *</label>
                        <select class="form-select" name="category" id="modalCategorySelect" required>
                            <option value="pengumuman">Edaran Kedinasan (Pengumuman Umum)</option>
                            <option value="perwira_baru">Info Perwira Baru (Kelulusan / Pelantikan)</option>
                            <option value="infografis">Infografis & Visual Data</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unggah Gambar / Infografis (Opsional)</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <small class="text-muted">Mendukung format JPG, PNG, WEBP untuk gambar infografis.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Uraian / Isi Konten *</label>
                        <textarea class="form-control" name="content" id="modalContentInput" rows="6" placeholder="Ketik rincian pengumuman lengkap di sini..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="modalSaveBtn">Terbitkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
const announcementModal = document.getElementById('announcementModal');
announcementModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const action = button.getAttribute('data-action');
    const modalTitle = document.getElementById('modalAnnTitle');
    const modalAction = document.getElementById('modalAnnAction');
    const modalId = document.getElementById('modalAnnId');
    const titleInput = document.getElementById('modalTitleInput');
    const categorySelect = document.getElementById('modalCategorySelect');
    const contentInput = document.getElementById('modalContentInput');
    const saveBtn = document.getElementById('modalSaveBtn');

    if (action === 'edit') {
        modalTitle.textContent = 'Edit Informasi / Pengumuman';
        modalAction.value = 'edit';
        modalId.value = button.getAttribute('data-id');
        titleInput.value = button.getAttribute('data-title');
        categorySelect.value = button.getAttribute('data-category') || 'pengumuman';
        contentInput.value = button.getAttribute('data-content');
        saveBtn.textContent = 'Simpan Perubahan';
    } else {
        modalTitle.textContent = 'Buat Informasi Baru';
        modalAction.value = 'add';
        modalId.value = '';
        titleInput.value = '';
        categorySelect.value = 'pengumuman';
        contentInput.value = '';
        saveBtn.textContent = 'Terbitkan';
    }
});
</script>
