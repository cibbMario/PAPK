<?php
// admin/import-export.php
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

// Handle Export CSV (Bisa dibuka langsung di Excel tanpa library eksternal)
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $stmt = $pdo->query("SELECT nrp_nia, name, `rank`, unit, location_name, latitude, longitude, phone, address, status FROM members ORDER BY id ASC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Data_Anggota_PAPK_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    // BOM UTF-8 agar Excel mengenali karakter khusus
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header kolom
    fputcsv($output, ['NRP/NIA', 'Nama Lengkap', 'Pangkat', 'Satuan', 'Lokasi Dinas', 'Latitude', 'Longitude', 'No HP', 'Alamat', 'Status']);

    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Handle Template Download
if (isset($_GET['action']) && $_GET['action'] === 'template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Template_Import_Anggota_PAPK.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['NRP/NIA', 'Nama Lengkap', 'Pangkat', 'Satuan', 'Lokasi Dinas', 'Latitude', 'Longitude', 'No HP', 'Alamat', 'Status']);
    fputcsv($output, ['523999', 'Letda Lek Contoh Perwira, S.Kom.', 'Letda', 'Skadron Udara 1', 'Lanud Supadio, Pontianak', '-0.150914', '109.404289', '081299988877', 'Komplek Lanud Supadio', 'aktif']);
    fclose($output);
    exit;
}

// Handle Import File (CSV / Excel format CSV)
$importResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    $file = $_FILES['file_excel'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('Gagal mengunggah file. Silakan periksa kembali file Anda.', 'danger');
        header('Location: import-export.php');
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv', 'txt'])) {
        setFlash('Format file tidak didukung! Harap unggah file berformat .CSV (bisa disimpan via Excel: Save As -> CSV).', 'warning');
        header('Location: import-export.php');
        exit;
    }

    $handle = fopen($file['tmp_name'], 'r');
    if ($handle !== false) {
        // Deteksi delimiter (, atau ;)
        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);

        // Lewati baris header
        fgetcsv($handle, 1000, $delimiter);

        $inserted = 0;
        $updated = 0;

        $stmt = $pdo->prepare("INSERT INTO members (nrp_nia, name, `rank`, unit, location_name, latitude, longitude, phone, address, status)
            VALUES (:nrp, :name, :rank, :unit, :loc, :lat, :lng, :phone, :addr, :status)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                `rank` = VALUES(`rank`),
                unit = VALUES(unit),
                location_name = VALUES(location_name),
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                phone = VALUES(phone),
                address = VALUES(address),
                status = VALUES(status)");

        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
            if (empty($row[0]) || empty($row[1])) continue;

            $nrp    = trim($row[0]);
            $name   = trim($row[1]);
            $rank   = trim($row[2] ?? 'Letda');
            $unit   = trim($row[3] ?? 'TNI AU');
            $loc    = trim($row[4] ?? '');
            $lat    = !empty($row[5]) ? trim($row[5]) : null;
            $lng    = !empty($row[6]) ? trim($row[6]) : null;
            $phone  = trim($row[7] ?? '');
            $addr   = trim($row[8] ?? '');
            $status = in_array(strtolower(trim($row[9] ?? '')), ['aktif', 'non-aktif', 'pensiun']) ? strtolower(trim($row[9])) : 'aktif';

            $stmt->execute([
                ':nrp'   => $nrp,
                ':name'  => $name,
                ':rank'  => $rank,
                ':unit'  => $unit,
                ':loc'   => $loc,
                ':lat'   => $lat,
                ':lng'   => $lng,
                ':phone' => $phone,
                ':addr'  => $addr,
                ':status'=> $status
            ]);
            $inserted++;
        }
        fclose($handle);

        setFlash("Berhasil mengimpor $inserted data anggota ke dalam sistem!", 'success');
        header('Location: import-export.php');
        exit;
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 font-serif fw-bold mb-1">Import & Export Data Anggota</h1>
        <p class="text-muted small mb-0">Pengelolaan berkas data anggota massal via CSV spreadsheet.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Lihat Data Anggota</a>
</div>

<div class="row g-4">
    <!-- Card Import -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold font-serif text-dark">Import Data Anggota (Excel / CSV)</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small">
                    Admin dapat memasukkan puluhan hingga ratusan data personel perwira sekaligus tanpa harus menginput manual satu per satu.
                </p>

                <div class="alert alert-light border small mb-4">
                    <strong>Petunjuk Format:</strong>
                    <ul class="mb-0 ps-3">
                        <li>Unduh template file terlebih dahulu agar urutan kolom cocok.</li>
                        <li>Format kolom: <code>NRP/NIA, Nama, Pangkat, Satuan, Lokasi Dinas, Latitude, Longitude, No HP, Alamat, Status</code></li>
                        <li>Simpan file Anda dengan tipe <strong>CSV (Comma Delimited)</strong> via Microsoft Excel.</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <a href="?action=template" class="btn btn-outline-dark btn-sm fw-semibold">
                        Unduh Format Template CSV
                    </a>
                </div>

                <form method="POST" action="import-export.php" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File CSV Hasil Simpan Excel</label>
                        <input class="form-control" type="file" name="file_excel" accept=".csv, .txt" required>
                    </div>
                    <button type="submit" class="btn btn-dark fw-semibold w-100">
                        Mulai Proses Import Data
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Card Export -->
    <div class="col-lg-6">
        <div class="card border h-100" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold font-serif text-dark">Export & Unduh Data Anggota</h5>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <p class="text-muted small">
                        Unduh salinan data seluruh anggota PAPK yang ada di database ke dalam format spreadsheet (Excel CSV) untuk kebutuhan pelaporan, arsip Mabesau, atau pencetakan fisik.
                    </p>

                    <div class="card bg-light border p-3 mb-4" style="border-radius: 8px;">
                        <div>
                            <h6 class="fw-bold mb-1 font-serif">Seluruh Database Personel</h6>
                            <p class="text-muted small mb-0">Termasuk NRP, Pangkat, Satuan, Pos Dinas, & Titik Peta Koordinat.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <a href="?action=export_csv" class="btn btn-dark fw-semibold w-100 py-2">
                        Export Data ke Excel (.CSV)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
