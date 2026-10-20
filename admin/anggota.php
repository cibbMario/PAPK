<?php
// admin/anggota.php
// List members with pagination, add/edit via modal, delete action, and toast notifications.
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

// ---------- Handle POST actions (add / edit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO members (nrp_nia, name, `rank`, unit, status) VALUES (:nrp, :name, :rank, :unit, :status)");
        $stmt->execute([
            ':nrp'   => $_POST['nrp_nia'],
            ':name'  => $_POST['name'],
            ':rank'  => $_POST['rank'],
            ':unit'  => $_POST['unit'],
            ':status'=> $_POST['status'] ?? 'aktif',
        ]);
        setFlash('Anggota berhasil ditambahkan.', 'success');
        header('Location: anggota.php');
        exit();
    }
    if ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE members SET nrp_nia=:nrp, name=:name, `rank`=:rank, unit=:unit, status=:status WHERE id=:id");
        $stmt->execute([
            ':id'    => $_POST['id'],
            ':nrp'   => $_POST['nrp_nia'],
            ':name'  => $_POST['name'],
            ':rank'  => $_POST['rank'],
            ':unit'  => $_POST['unit'],
            ':status'=> $_POST['status'] ?? 'aktif',
        ]);
        setFlash('Data anggota berhasil diperbarui.', 'success');
        header('Location: anggota.php');
        exit();
    }
}

// ---------- Handle Delete (GET) ----------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM members WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);
    setFlash('Anggota berhasil dihapus.', 'danger');
    header('Location: anggota.php');
    exit();
}

// ---------- Pagination ----------
$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Optional simple search (by name or NRP)
$search = trim($_GET['search'] ?? '');
$searchSql = '';
$params = [];
if ($search !== '') {
    $searchSql = "WHERE name LIKE :kw OR nrp_nia LIKE :kw";
    $params[':kw'] = "%$search%";
}

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM members $searchSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Fetch current page rows
$dataStmt = $pdo->prepare("SELECT * FROM members $searchSql ORDER BY id DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k=>$v) $dataStmt->bindValue($k, $v);
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$members = $dataStmt->fetchAll();
?>
<h1 class="mt-4 font-serif">Daftar Anggota</h1>
<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex" method="get" action="anggota.php">
        <input class="form-control me-2" type="search" name="search" placeholder="Cari Nama / NRP" value="<?= htmlspecialchars($search) ?>" />
        <button class="btn btn-outline-dark" type="submit">Cari</button>
    </form>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#memberModal" data-action="add">Tambah Anggota</button>
</div>
<table class="table table-sm table-hover">
    <thead class="table-light">
        <tr>
            <th>#</th>
            <th>NRP/NIA</th>
            <th>Nama</th>
            <th>Pangkat</th>
            <th>Satuan</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($members as $m): ?>
        <tr>
            <td><?= $m['id'] ?></td>
            <td><?= htmlspecialchars($m['nrp_nia']) ?></td>
            <td><?= htmlspecialchars($m['name']) ?></td>
            <td><?= htmlspecialchars($m['rank']) ?></td>
            <td><?= htmlspecialchars($m['unit']) ?></td>
            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($m['status']) ?></span></td>
            <td>
                <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#memberModal"
                        data-action="edit" data-id="<?= $m['id'] ?>" data-nrp="<?= htmlspecialchars($m['nrp_nia']) ?>"
                        data-name="<?= htmlspecialchars($m['name']) ?>" data-rank="<?= htmlspecialchars($m['rank']) ?>"
                        data-unit="<?= htmlspecialchars($m['unit']) ?>" data-status="<?= htmlspecialchars($m['status']) ?>">
                    Edit
                </button>
                <a href="?action=delete&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-dark" onclick="return confirm('Hapus anggota ini?');">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($members)): ?>
        <tr><td colspan="7" class="text-center">Tidak ada data.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<!-- Pagination -->
<nav>
    <ul class="pagination">
        <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">Previous</a></li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link">Previous</span></li>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next</a></li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link">Next</span></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Modal for Add / Edit -->
<div class="modal fade" id="memberModal" tabindex="-1" aria-labelledby="memberModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="anggota.php">
        <div class="modal-header">
          <h5 class="modal-title" id="memberModalLabel">Tambah / Edit Anggota</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="modalAction" value="add" />
          <input type="hidden" name="id" id="modalId" value="" />
          <div class="mb-3">
            <label class="form-label">NRP / NIA</label>
            <input type="text" class="form-control" name="nrp_nia" id="modalNrp" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control" name="name" id="modalName" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Pangkat</label>
            <input type="text" class="form-control" name="rank" id="modalRank" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Satuan</label>
            <input type="text" class="form-control" name="unit" id="modalUnit" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="modalStatus">
              <option value="aktif">Aktif</option>
              <option value="non-aktif">Non-aktif</option>
              <option value="pensiun">Pensiun</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
    // Populate modal for edit action
    const memberModal = document.getElementById('memberModal');
    memberModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // button that triggered modal
        const action = button.getAttribute('data-action');
        const modalTitle = memberModal.querySelector('.modal-title');
        const modalActionInput = document.getElementById('modalAction');
        const modalIdInput = document.getElementById('modalId');
        const modalNrp = document.getElementById('modalNrp');
        const modalName = document.getElementById('modalName');
        const modalRank = document.getElementById('modalRank');
        const modalUnit = document.getElementById('modalUnit');
        const modalStatus = document.getElementById('modalStatus');

        if (action === 'edit') {
            modalTitle.textContent = 'Edit Anggota';
            modalActionInput.value = 'edit';
            modalIdInput.value = button.getAttribute('data-id');
            modalNrp.value = button.getAttribute('data-nrp');
            modalName.value = button.getAttribute('data-name');
            modalRank.value = button.getAttribute('data-rank');
            modalUnit.value = button.getAttribute('data-unit');
            modalStatus.value = button.getAttribute('data-status');
        } else { // add
            modalTitle.textContent = 'Tambah Anggota';
            modalActionInput.value = 'add';
            modalIdInput.value = '';
            modalNrp.value = '';
            modalName.value = '';
            modalRank.value = '';
            modalUnit.value = '';
            modalStatus.value = 'aktif';
        }
    });
</script>
