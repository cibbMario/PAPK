<?php
require_once __DIR__ . '/../config/koneksi.php';

// Pastikan admin & johndoe berstatus approved
$pdo->exec("UPDATE users SET status = 'approved' WHERE username IN ('admin', 'johndoe')");

// Tambah/Update anggota dengan koordinat Lanud / Satuan TNI AU di Indonesia
$membersData = [
    [
        'nrp' => '523451',
        'name' => 'Mayor Adm Agus Santoso, S.T.',
        'rank' => 'Mayor',
        'unit' => 'Disminpersau - Mabesau',
        'loc' => 'Mabesau Cilangkap, Jakarta',
        'lat' => -6.326884,
        'lng' => 106.902264,
        'phone' => '081234567890',
        'addr' => 'Komplek Halim Perdanakusuma, Jakarta Timur'
    ],
    [
        'nrp' => '523452',
        'name' => 'Kapten Tek Budi Prasetyo, M.T.',
        'rank' => 'Kapten',
        'unit' => 'Depohar 20 Koharmatau',
        'loc' => 'Lanud Iswahjudi, Madiun',
        'lat' => -7.616616,
        'lng' => 111.433604,
        'phone' => '081234567891',
        'addr' => 'Komplek Lanud Iswahjudi, Magetan'
    ],
    [
        'nrp' => '523453',
        'name' => 'Lettu Lek Citra Dewi, S.Kom.',
        'rank' => 'Lettu',
        'unit' => 'Skadron Udara 5',
        'loc' => 'Lanud Sultan Hasanuddin, Makassar',
        'lat' => -5.061633,
        'lng' => 119.554030,
        'phone' => '081234567892',
        'addr' => 'Komplek Skadron 5 Lanud Hasanuddin'
    ],
    [
        'nrp' => '523454',
        'name' => 'Letda Kes dr. Dedi Irawan',
        'rank' => 'Letda',
        'unit' => 'Rumkit RSAU dr. Esnawan',
        'loc' => 'Lanud Halim Perdanakusuma, Jakarta',
        'lat' => -6.265747,
        'lng' => 106.891157,
        'phone' => '081234567893',
        'addr' => 'Mess Perwira Halim'
    ],
    [
        'nrp' => '523455',
        'name' => 'Kolonel Sus Eko Nugroho, S.H.',
        'rank' => 'Kolonel',
        'unit' => 'Lanud Roesmin Nurjadin',
        'loc' => 'Lanud Roesmin Nurjadin, Pekanbaru',
        'lat' => 0.461129,
        'lng' => 101.444747,
        'phone' => '081234567894',
        'addr' => 'Pekanbaru, Riau'
    ],
    [
        'nrp' => '523456',
        'name' => 'Kapten Kal Fajar Hidayat, S.E.',
        'rank' => 'Kapten',
        'unit' => 'Dinas Logistik',
        'loc' => 'Lanud Supadio, Pontianak',
        'lat' => -0.150914,
        'lng' => 109.404289,
        'phone' => '081234567895',
        'addr' => 'Kubu Raya, Kalimantan Barat'
    ],
    [
        'nrp' => '523457',
        'name' => 'Lettu Adm Giska Putri, S.A.P.',
        'rank' => 'Lettu',
        'unit' => 'Lanud Silas Papare',
        'loc' => 'Lanud Silas Papare, Jayapura',
        'lat' => -2.576856,
        'lng' => 140.516130,
        'phone' => '081234567896',
        'addr' => 'Sentani, Jayapura, Papua'
    ],
    [
        'nrp' => '523458',
        'name' => 'Kapten Lek Hendra Kurnia, S.T.',
        'rank' => 'Kapten',
        'unit' => 'Satrad 243 Timika',
        'loc' => 'Satrad 243, Mimika Papua',
        'lat' => -4.528438,
        'lng' => 136.887019,
        'phone' => '081234567897',
        'addr' => 'Timika, Papua Tengah'
    ],
    [
        'nrp' => '523459',
        'name' => 'Mayor Sus Ilham Maulana, S.Ag.',
        'rank' => 'Mayor',
        'unit' => 'Lanud Sam Ratulangi',
        'loc' => 'Lanud Sam Ratulangi, Manado',
        'lat' => 1.549298,
        'lng' => 124.925763,
        'phone' => '081234567898',
        'addr' => 'Mapanget, Manado, Sulawesi Utara'
    ]
];

$stmt = $pdo->prepare("INSERT INTO members (nrp_nia, name, `rank`, unit, location_name, latitude, longitude, phone, address, status) 
VALUES (:nrp, :name, :rank, :unit, :loc, :lat, :lng, :phone, :addr, 'aktif')
ON DUPLICATE KEY UPDATE 
  name = VALUES(name), 
  `rank` = VALUES(`rank`), 
  unit = VALUES(unit), 
  location_name = VALUES(location_name), 
  latitude = VALUES(latitude), 
  longitude = VALUES(longitude),
  phone = VALUES(phone),
  address = VALUES(address)");

foreach ($membersData as $m) {
    $stmt->execute($m);
}

// Tambahkan pengumuman kategori perwira baru & infografis
$pdo->exec("DELETE FROM announcements");
$announcements = [
    [
        'title' => 'Penyambutan Perwira Prajurit Karier (PAPK) TNI AU Angkatan XXXII Gelombang I',
        'category' => 'perwira_baru',
        'content' => 'Selamat datang dan selamat bergabung kepada 45 Perwira Remaja PAPK TNI AU Angkatan XXXII. Para perwira telah resmi dilantik dan mulai terdistribusi di satuan kerja Komando Operasi Udara Nasional (Koopsudnas), Kodiklatau, Koharmatau, dan Korpasgat.',
        'image' => null
    ],
    [
        'title' => 'Infografis: Sebaran Pangkat dan Korps Perwira Karier TNI AU 2026',
        'category' => 'infografis',
        'content' => 'Berikut adalah infografis komprehensif mengenai proporsi persebaran Korps Perwira Karier: Korps Tek (30%), Lek (25%), Adm (20%), Kes (15%), dan Sus (10%) di seluruh wilayah komando dari Sabang sampai Merauke.',
        'image' => null
    ],
    [
        'title' => 'Instruksi Pemutakhiran Data Wilayah Dinas & Penempatan Personel',
        'category' => 'pengumuman',
        'content' => 'Diinstruksikan kepada seluruh perwira yang mengalami mutasi/rotasi satuan kerja (Tour of Duty / Tour of Area) untuk segera mengajukan update koordinat satuan dinas pada sistem portal PAPK.',
        'image' => null
    ]
];

$insAnn = $pdo->prepare("INSERT INTO announcements (title, category, content, created_by) VALUES (?, ?, ?, 1)");
foreach ($announcements as $a) {
    $insAnn->execute([$a['title'], $a['category'], $a['content']]);
}

echo "Database seeded with map coordinates and announcements!\n";
