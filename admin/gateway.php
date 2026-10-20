<?php
// admin/gateway.php
// WhatsApp & Notification Gateway Management Dashboard
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');
require_once __DIR__ . '/../includes/notifier.php';

// Handle Simpan Pengaturan Gateway
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    setGatewaySetting('wa_provider', trim($_POST['wa_provider'] ?? 'fonnte'));
    setGatewaySetting('wa_mode', trim($_POST['wa_mode'] ?? 'simulation'));
    setGatewaySetting('wa_api_token', trim($_POST['wa_api_token'] ?? ''));
    setGatewaySetting('wa_sender_number', trim($_POST['wa_sender_number'] ?? ''));
    setGatewaySetting('notify_approval', isset($_POST['notify_approval']) ? '1' : '0');
    setGatewaySetting('notify_rejection', isset($_POST['notify_rejection']) ? '1' : '0');
    setGatewaySetting('notify_announcement', isset($_POST['notify_announcement']) ? '1' : '0');

    setFlash('Pengaturan WhatsApp & Notifikasi Gateway berhasil disimpan!', 'success');
    header('Location: gateway.php');
    exit;
}

// Handle Tes Pengiriman WhatsApp Manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test_wa'])) {
    $targetPhone = trim($_POST['test_phone'] ?? '');
    $targetName  = trim($_POST['test_name'] ?? 'Perwira PAPK');
    $title       = trim($_POST['test_title'] ?? 'Uji Coba Pengiriman');
    $message     = trim($_POST['test_message'] ?? 'Ini adalah pesan pengujian koneksi WhatsApp Gateway PAPK TNI AU.');

    if (!empty($targetPhone) && !empty($message)) {
        $sent = dispatchWhatsApp($targetPhone, $targetName, $title, $message);
        if ($sent) {
            setFlash("Pesan uji coba berhasil diproses ke nomor $targetPhone (" . strtoupper(getGatewaySetting('wa_mode', 'simulation')) . " mode)!", 'success');
        } else {
            setFlash('Gagal memproses pesan pengujian.', 'danger');
        }
    } else {
        setFlash('Harap isi nomor tujuan dan pesan pengujian.', 'warning');
    }
    header('Location: gateway.php');
    exit;
}

// Handle Hapus Log
if (isset($_GET['action']) && $_GET['action'] === 'clear_logs') {
    $pdo->exec("TRUNCATE TABLE notification_logs");
    setFlash('Seluruh riwayat log notifikasi berhasil dibersihkan.', 'info');
    header('Location: gateway.php');
    exit;
}

// Ambil Pengaturan Saat Ini
$settings = [
    'wa_provider'         => getGatewaySetting('wa_provider', 'fonnte'),
    'wa_mode'             => getGatewaySetting('wa_mode', 'simulation'),
    'wa_api_token'        => getGatewaySetting('wa_api_token', ''),
    'wa_sender_number'    => getGatewaySetting('wa_sender_number', '0811-9876-PAPK'),
    'notify_approval'     => getGatewaySetting('notify_approval', '1'),
    'notify_rejection'    => getGatewaySetting('notify_rejection', '1'),
    'notify_announcement' => getGatewaySetting('notify_announcement', '1'),
];

// Ambil Statistik Notifikasi
$totalLogs = $pdo->query("SELECT COUNT(*) FROM notification_logs")->fetchColumn();
$simulatedCount = $pdo->query("SELECT COUNT(*) FROM notification_logs WHERE status = 'simulated'")->fetchColumn();
$sentCount = $pdo->query("SELECT COUNT(*) FROM notification_logs WHERE status = 'sent'")->fetchColumn();
$failedCount = $pdo->query("SELECT COUNT(*) FROM notification_logs WHERE status = 'failed'")->fetchColumn();

// Ambil Riwayat Log Terkini
$stmtLogs = $pdo->query("SELECT * FROM notification_logs ORDER BY id DESC LIMIT 50");
$logs = $stmtLogs->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1"><i class="bi bi-broadcast text-success me-2"></i>WhatsApp & Notification Gateway</h1>
        <p class="text-muted small mb-0">Manajemen pengiriman notifikasi otomatis dan integrasi WhatsApp dinas personel PAPK TNI AU.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="gateway.php" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-arrow-clockwise"></i> Refresh</a>
        <a href="gateway.php?action=clear_logs" onclick="return confirm('Kosongkan semua log pengiriman?');" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Hapus Log</a>
    </div>
</div>

<!-- Statistik Kartu -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-primary">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold">Status Gateway Saat Ini</div>
                <div class="d-flex align-items-center mt-1">
                    <?php if ($settings['wa_mode'] === 'live'): ?>
                        <span class="badge bg-success me-2 fs-6"><i class="bi bi-check-circle-fill"></i> LIVE API</span>
                        <small class="text-muted"><?= strtoupper($settings['wa_provider']) ?></small>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark me-2 fs-6"><i class="bi bi-cpu-fill"></i> SIMULASI</span>
                        <small class="text-muted">Safe Demo Mode</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-info">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold">Total Log Notifikasi</div>
                <div class="h3 mb-0 fw-bold text-dark mt-1"><?= number_format($totalLogs) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold">Terkirim / Live API</div>
                <div class="h3 mb-0 fw-bold text-success mt-1"><?= number_format($sentCount) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-secondary">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold">Tercatat Simulasi</div>
                <div class="h3 mb-0 fw-bold text-secondary mt-1"><?= number_format($simulatedCount) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Form Pengaturan Gateway -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                <i class="bi bi-sliders text-primary me-2 fs-5"></i>
                <h5 class="mb-0 fw-bold">Konfigurasi WhatsApp Gateway API</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="gateway.php">
                    <input type="hidden" name="save_settings" value="1">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mode Operasional *</label>
                            <select class="form-select" name="wa_mode">
                                <option value="simulation" <?= $settings['wa_mode'] === 'simulation' ? 'selected' : '' ?>>
                                    Mode Simulasi (Aman / Demo & Catat Log)
                                </option>
                                <option value="live" <?= $settings['wa_mode'] === 'live' ? 'selected' : '' ?>>
                                    Mode Live (Kirim WA Nyata via API Provider)
                                </option>
                            </select>
                            <div class="form-text">Pada mode simulasi, sistem tetap memformat pesan resmi dan mencatatnya ke log tanpa perlu token berbayar.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Penyedia Layanan API</label>
                            <select class="form-select" name="wa_provider">
                                <option value="fonnte" <?= $settings['wa_provider'] === 'fonnte' ? 'selected' : '' ?>>Fonnte (Rekomendasi Indonesia)</option>
                                <option value="wablas" <?= $settings['wa_provider'] === 'wablas' ? 'selected' : '' ?>>Wablas</option>
                                <option value="custom" <?= $settings['wa_provider'] === 'custom' ? 'selected' : '' ?>>Custom Webhook Gateway</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">API Token / Secret Key</label>
                        <input type="password" class="form-control" name="wa_api_token" value="<?= htmlspecialchars($settings['wa_api_token']) ?>" placeholder="Masukkan API Token dari Fonnte / Provider">
                        <div class="form-text">Kosongkan jika menggunakan Mode Simulasi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor Pengirim Resmi (Sender ID / Bot Name)</label>
                        <input type="text" class="form-control" name="wa_sender_number" value="<?= htmlspecialchars($settings['wa_sender_number']) ?>" placeholder="Contoh: 0811-9876-PAPK / Mabesau Bot">
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3"><i class="bi bi-bell-fill text-warning me-2"></i>Pemicu Notifikasi Otomatis</h6>
                    
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="notify_approval" id="checkAppr" value="1" <?= $settings['notify_approval'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="checkAppr">
                            Kirim WA saat Pengajuan Perubahan Data Disetujui
                        </label>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="notify_rejection" id="checkRej" value="1" <?= $settings['notify_rejection'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="checkRej">
                            Kirim WA saat Pengajuan Perubahan Data Ditolak
                        </label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="notify_announcement" id="checkAnn" value="1" <?= $settings['notify_announcement'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="checkAnn">
                            Izinkan Siaran (Broadcast) Massal saat Menerbitkan Pengumuman Baru
                        </label>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i> Simpan Konfigurasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Pengujian Langsung -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                <i class="bi bi-send-fill text-success me-2 fs-5"></i>
                <h5 class="mb-0 fw-bold">Uji Coba Kirim Notifikasi</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted">Gunakan modul ini untuk menguji format pesan WhatsApp dan memastikan alur gateway berjalan lancar.</p>
                <form method="POST" action="gateway.php">
                    <input type="hidden" name="send_test_wa" value="1">
                    
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Nomor WhatsApp Tujuan *</label>
                        <input type="text" class="form-control form-control-sm" name="test_phone" placeholder="Contoh: 081234567890" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Nama Penerima</label>
                        <input type="text" class="form-control form-control-sm" name="test_name" value="Letda Sus Yanuar Mario, S.Tr.Han.">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Judul Notifikasi</label>
                        <input type="text" class="form-control form-control-sm" name="test_title" value="Pemberitahuan Pemeriksaan Kesehatan">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Isi Pesan Notifikasi *</label>
                        <textarea class="form-control form-control-sm" name="test_message" rows="3" required>Diberitahukan kepada Letda Sus Yanuar untuk menghadiri verifikasi berkas personel di Mabesau besok pukul 08.00 WIB.</textarea>
                    </div>

                    <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold">
                        <i class="bi bi-whatsapp me-1"></i> Kirim Pesan Uji Coba Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Riwayat Log Notifikasi -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Log Pengiriman Pesan Terkini (50 Terakhir)</h5>
        <span class="badge bg-secondary"><?= count($logs) ?> Catatan</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Waktu</th>
                        <th>Penerima</th>
                        <th>Nomor Tujuan</th>
                        <th>Judul Pesan</th>
                        <th>Isi Pesan</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $i => $log): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $i + 1 ?></td>
                                <td class="small text-nowrap"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($log['recipient_name'] ?: '-') ?></td>
                                <td><code><?= htmlspecialchars($log['recipient']) ?></code></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($log['title']) ?></span></td>
                                <td>
                                    <button class="btn btn-xs btn-outline-secondary btn-sm py-0" data-bs-toggle="collapse" data-bs-target="#msgDetail<?= $log['id'] ?>">
                                        Lihat Isi Pesan
                                    </button>
                                    <div class="collapse mt-2" id="msgDetail<?= $log['id'] ?>">
                                        <div class="card card-body p-2 bg-light border small text-monospace" style="font-size: 11px; white-space: pre-wrap;">
<?= htmlspecialchars($log['message']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($log['status'] === 'sent'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-all"></i> Terkirim</span>
                                    <?php elseif ($log['status'] === 'simulated'): ?>
                                        <span class="badge bg-info text-dark"><i class="bi bi-cpu"></i> Simulasi</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Gagal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada aktivitas log pengiriman pesan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
