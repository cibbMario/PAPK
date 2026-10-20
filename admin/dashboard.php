<?php
// admin/dashboard.php
// Admin dashboard with full member management (CRUD, search, pagination, modal, and summary cards)
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

// ---------- Handle POST actions (add / edit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Upload foto anggota (jika ada file diunggah)
    $uploadedPhoto = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExts) && $_FILES['photo']['size'] <= 2 * 1024 * 1024) {
            $filename = 'member_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/avatars/' . $filename;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $uploadedPhoto = 'uploads/avatars/' . $filename;
            }
        }
    }

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO members (nrp_nia, name, `rank`, unit, location_name, phone, status, photo_path) 
                              VALUES (:nrp, :name, :rank, :unit, :loc, :phone, :status, :photo)");
        $stmt->execute([
            ':nrp'   => trim($_POST['nrp_nia']),
            ':name'  => trim($_POST['name']),
            ':rank'  => trim($_POST['rank']),
            ':unit'  => trim($_POST['unit']),
            ':loc'   => trim($_POST['location_name'] ?? ''),
            ':phone' => trim($_POST['phone'] ?? ''),
            ':status'=> $_POST['status'] ?? 'aktif',
            ':photo' => $uploadedPhoto
        ]);
        setFlash('Anggota berhasil ditambahkan.', 'success');
        header('Location: dashboard.php');
        exit();
    }
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        if ($uploadedPhoto) {
            $stmt = $pdo->prepare("UPDATE members SET nrp_nia=:nrp, name=:name, `rank`=:rank, unit=:unit, location_name=:loc, phone=:phone, status=:status, photo_path=:photo WHERE id=:id");
            $params = [
                ':id'    => $id,
                ':nrp'   => trim($_POST['nrp_nia']),
                ':name'  => trim($_POST['name']),
                ':rank'  => trim($_POST['rank']),
                ':unit'  => trim($_POST['unit']),
                ':loc'   => trim($_POST['location_name'] ?? ''),
                ':phone' => trim($_POST['phone'] ?? ''),
                ':status'=> $_POST['status'] ?? 'aktif',
                ':photo' => $uploadedPhoto
            ];
        } else {
            $stmt = $pdo->prepare("UPDATE members SET nrp_nia=:nrp, name=:name, `rank`=:rank, unit=:unit, location_name=:loc, phone=:phone, status=:status WHERE id=:id");
            $params = [
                ':id'    => $id,
                ':nrp'   => trim($_POST['nrp_nia']),
                ':name'  => trim($_POST['name']),
                ':rank'  => trim($_POST['rank']),
                ':unit'  => trim($_POST['unit']),
                ':loc'   => trim($_POST['location_name'] ?? ''),
                ':phone' => trim($_POST['phone'] ?? ''),
                ':status'=> $_POST['status'] ?? 'aktif'
            ];
        }
        $stmt->execute($params);
        setFlash('Data anggota berhasil diperbarui.', 'success');
        header('Location: dashboard.php');
        exit();
    }
}

// ---------- Handle Delete (GET) ----------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM members WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);
    setFlash('Anggota berhasil dihapus.', 'danger');
    header('Location: dashboard.php');
    exit();
}

// ---------- Pagination & Search ----------
$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$filterRank = trim($_GET['rank'] ?? '');
$searchSql = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $searchSql .= " AND (name LIKE :kw OR nrp_nia LIKE :kw OR unit LIKE :kw)";
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

// Fetch members
$dataStmt = $pdo->prepare("SELECT * FROM members $searchSql ORDER BY id DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) {
    $dataStmt->bindValue($k, $v);
}
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$members = $dataStmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Dashboard Administrator</h1>
        <p class="text-muted small mb-0">Kelola dan pantau seluruh data personel anggota PAPK TNI AU.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#memberModal" data-action="add">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Anggota Baru
        </button>
    </div>
</div>

<!-- Member Management Table Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <div class="row align-items-center g-2">
            <div class="col-md-4">
                <h5 class="mb-0 fw-bold">Daftar Seluruh Anggota PAPK</h5>
            </div>
            <div class="col-md-8">
                <form class="d-flex justify-content-md-end gap-2" method="get" action="dashboard.php">
                    <input class="form-control form-control-sm" style="max-width: 250px;" type="search" name="search" 
                           placeholder="Cari Nama / NRP / Satuan..." value="<?= htmlspecialchars($search) ?>" />
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="dashboard.php" class="btn btn-sm btn-link text-decoration-none text-muted">Reset</a>
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
                        <th style="width: 15%;">NRP/NIA</th>
                        <th style="width: 25%;">Nama Lengkap</th>
                        <th style="width: 12%;">Pangkat</th>
                        <th style="width: 20%;">Satuan / Pos Dinas</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 13%;" class="pe-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($members)): ?>
                        <?php foreach ($members as $m): ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $m['id'] ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($m['nrp_nia']) ?></span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php 
                                        $avatarImg = (!empty($m['photo_path']) && file_exists(__DIR__ . '/../' . $m['photo_path'])) 
                                                     ? '../' . htmlspecialchars($m['photo_path']) 
                                                     : "https://ui-avatars.com/api/?name=" . urlencode($m['name']) . "&size=40&background=1e3c72&color=fff";
                                    ?>
                                    <img src="<?= $avatarImg ?>" class="rounded-circle me-2 border shadow-sm" style="width: 38px; height: 38px; object-fit: cover;" alt="Avatar">
                                    <div>
                                        <strong><?= htmlspecialchars($m['name']) ?></strong><br>
                                        <small class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($m['phone'] ?: '-') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($m['rank']) ?></span></td>
                            <td>
                                <div><?= htmlspecialchars($m['unit']) ?></div>
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
                                <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#memberModal"
                                        data-action="edit" 
                                        data-id="<?= $m['id'] ?>" 
                                        data-nrp="<?= htmlspecialchars($m['nrp_nia']) ?>"
                                        data-name="<?= htmlspecialchars($m['name']) ?>" 
                                        data-rank="<?= htmlspecialchars($m['rank']) ?>"
                                        data-unit="<?= htmlspecialchars($m['unit']) ?>" 
                                        data-loc="<?= htmlspecialchars($m['location_name'] ?? '') ?>"
                                        data-phone="<?= htmlspecialchars($m['phone'] ?? '') ?>"
                                        data-status="<?= htmlspecialchars($m['status']) ?>">
                                    Edit
                                </button>
                                <a href="?action=delete&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus anggota ini?');">
                                    Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ada data anggota yang cocok dengan pencarian.
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
            <small class="text-muted">Menampilkan halaman <?= $page ?> dari <?= $totalPages ?> (Total: <?= $totalRows ?> data)</small>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Modal for Add / Edit Member -->
<div class="modal fade" id="memberModal" tabindex="-1" aria-labelledby="memberModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="dashboard.php" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="memberModalLabel">Tambah / Edit Anggota</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="modalAction" value="add" />
          <input type="hidden" name="id" id="modalId" value="" />
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">NRP / NIA *</label>
              <input type="text" class="form-control" name="nrp_nia" id="modalNrp" placeholder="Contoh: 523881" required />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Nama Lengkap & Gelar *</label>
              <input type="text" class="form-control" name="name" id="modalName" placeholder="Contoh: Mayor Adm Agus Santoso, S.T." required />
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Pangkat *</label>
              <select class="form-select" name="rank" id="modalRank" required>
                <option value="Letda">Letda (Letnan Dua)</option>
                <option value="Lettu">Lettu (Letnan Satu)</option>
                <option value="Kapten">Kapten</option>
                <option value="Mayor">Mayor</option>
                <option value="Letkol">Letkol (Letnan Kolonel)</option>
                <option value="Kolonel">Kolonel</option>
                <option value="Marsekal Pertama">Marsma (Marsekal Pertama)</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Satuan Kerja / Kesatuan *</label>
              <input type="text" class="form-control" name="unit" id="modalUnit" placeholder="Contoh: Skadron Udara 5 / Disminpersau" required />
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Nama Pangkalan / Pos Dinas</label>
              <input type="text" class="form-control" name="location_name" id="modalLoc" placeholder="Contoh: Lanud Halim Perdanakusuma, Jakarta" />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Nomor HP / WhatsApp</label>
              <input type="text" class="form-control" name="phone" id="modalPhone" placeholder="Contoh: 081234567890" />
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Status Keanggotaan</label>
              <select class="form-select" name="status" id="modalStatus">
                <option value="aktif">Aktif</option>
                <option value="non-aktif">Non-aktif</option>
                <option value="pensiun">Pensiun</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Foto Profil Anggota</label>
              <input class="form-control" type="file" name="photo" accept="image/*">
              <small class="text-muted">Maksimal 2MB (JPG, PNG, WEBP). Kosongkan jika tidak diubah.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Simpan Data Anggota</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    // Populate modal for add / edit action
    const memberModal = document.getElementById('memberModal');
    memberModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-action');
        const modalTitle = document.getElementById('memberModalLabel');
        const modalActionInput = document.getElementById('modalAction');
        const modalIdInput = document.getElementById('modalId');
        const modalNrp = document.getElementById('modalNrp');
        const modalName = document.getElementById('modalName');
        const modalRank = document.getElementById('modalRank');
        const modalUnit = document.getElementById('modalUnit');
        const modalLoc = document.getElementById('modalLoc');
        const modalPhone = document.getElementById('modalPhone');
        const modalStatus = document.getElementById('modalStatus');
        const modalSubmitBtn = document.getElementById('modalSubmitBtn');

        if (action === 'edit') {
            modalTitle.textContent = 'Edit Data Anggota';
            modalActionInput.value = 'edit';
            modalIdInput.value = button.getAttribute('data-id');
            modalNrp.value = button.getAttribute('data-nrp');
            modalName.value = button.getAttribute('data-name');
            modalRank.value = button.getAttribute('data-rank');
            modalUnit.value = button.getAttribute('data-unit');
            modalLoc.value = button.getAttribute('data-loc') || '';
            modalPhone.value = button.getAttribute('data-phone') || '';
            modalStatus.value = button.getAttribute('data-status');
            modalSubmitBtn.textContent = 'Simpan Perubahan';
        } else {
            modalTitle.textContent = 'Tambah Anggota Baru';
            modalActionInput.value = 'add';
            modalIdInput.value = '';
            modalNrp.value = '';
            modalName.value = '';
            modalRank.value = 'Letda';
            modalUnit.value = '';
            modalLoc.value = '';
            modalPhone.value = '';
            modalStatus.value = 'aktif';
            modalSubmitBtn.textContent = 'Tambah Anggota';
        }
    });
</script>
