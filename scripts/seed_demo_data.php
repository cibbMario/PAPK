<?php
require_once __DIR__ . '/../config/koneksi.php';

// 1. Masukkan sampel data anggota jika belum ada
$check = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
if ($check == 0) {
    $members = [
        ['523451', 'Mayor Adm Agus Santoso, S.T.', 'Mayor', 'Disminpersau', 'aktif', '081234567890', 'Komplek Halim Perdanakusuma, Jakarta Timur'],
        ['523452', 'Kapten Tek Budi Prasetyo, M.T.', 'Kapten', 'Koharmatau', 'aktif', '081234567891', 'Lanud Iswahjudi, Madiun'],
        ['523453', 'Lettu Lek Citra Dewi, S.Kom.', 'Lettu', 'Disinfolahtau', 'aktif', '081234567892', 'Mabesau Cilangkap, Jakarta Timur'],
        ['523454', 'Letda Kes Dedi Irawan, S.Ked.', 'Letda', 'Diskesau', 'aktif', '081234567893', 'RSAU dr. Esnawan Antariksa'],
        ['523455', 'Kolonel Sus Eko Nugroho, S.H.', 'Kolonel', 'Dispenau', 'aktif', '081234567894', 'Mabesau Cilangkap, Jakarta Timur']
    ];

    $insStmt = $pdo->prepare("INSERT INTO members (nrp_nia, name, `rank`, unit, status, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($members as $m) {
        $insStmt->execute($m);
    }
    echo "Sample members inserted.\n";
}

// Hubungkan akun 'johndoe' ke member id 1 (Mayor Adm Agus Santoso)
$firstMemberId = $pdo->query("SELECT id FROM members ORDER BY id ASC LIMIT 1")->fetchColumn();
if ($firstMemberId) {
    $upd = $pdo->prepare("UPDATE users SET member_id = :mid WHERE username = 'johndoe'");
    $upd->execute([':mid' => $firstMemberId]);
    echo "johndoe linked to member id: $firstMemberId\n";
}

// 2. Masukkan pengumuman contoh
$checkAnn = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
if ($checkAnn == 0) {
    $adminId = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn() ?: 1;
    $announcements = [
        [
            'title' => 'Pemutakhiran Data Mandiri Anggota PAPK TNI AU TA 2026',
            'content' => 'Diberitahukan kepada seluruh Perwira PK (PAPK) TNI AU untuk segera memperbarui data pribadi seperti alamat domisili, nomor kontak HP, dan status keluarga pada portal PAPK sebelum akhir triwulan ini.',
            'created_by' => $adminId
        ],
        [
            'title' => 'Jadwal Pengecekan Fisik dan Kesehatan Berkala (Rikkes)',
            'content' => 'Pelaksanaan pemeriksaan kesehatan berkala bagi seluruh personel perwira akan diadakan mulai tanggal 1 s/d 10 bulan depan di fasilitas kesehatan masing-masing Lanud/Satuan.',
            'created_by' => $adminId
        ]
    ];

    $annStmt = $pdo->prepare("INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)");
    foreach ($announcements as $a) {
        $annStmt->execute([$a['title'], $a['content'], $a['created_by']]);
    }
    echo "Sample announcements inserted.\n";
}

echo "Database initialized successfully!\n";
