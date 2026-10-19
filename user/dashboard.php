<?php
// user/dashboard.php
require_once __DIR__ . '/../includes/header.php';
requireRole('user');

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ambil semua data anggota yang memiliki koordinat lokasi dinas
$stmtMembers = $pdo->query("SELECT id, nrp_nia, name, `rank`, unit, location_name, latitude, longitude, phone, status 
                            FROM members 
                            WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND status = 'aktif'");
$locations = $stmtMembers->fetchAll();

// Total anggota & sebaran
$totalDinas = count($locations);
$totalAnggota = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'aktif'")->fetchColumn();
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<style>
    #indonesiaMap {
        height: 600px;
        width: 100%;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        border: 1px solid #dee2e6;
        z-index: 1;
    }
    .custom-popup .leaflet-popup-content-wrapper {
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Dashboard Anggota</h1>
        <p class="text-muted small mb-0">Peta sebaran dinas personel Perwira Prajurit Karier (PAPK) TNI AU di seluruh Indonesia.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-primary fs-6 p-2">Halo, <?= htmlspecialchars($username) ?></span>
    </div>
</div>

<!-- Map Card Container -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Peta Sebaran Wilayah Dinas</h5>
        <span class="badge bg-light text-muted border">Ina-Geoportal Basemap</span>
    </div>
    <div class="card-body p-3">
        <div id="indonesiaMap"></div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    // Koordinat tengah Indonesia
    const indonesiaCenter = [-2.0, 118.0];
    const map = L.map('indonesiaMap').setView(indonesiaCenter, 5);

    // Basemap Topografi Biru Cerah (mirip Ina-Geoportal)
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 18,
        attribution: '&copy; Esri, OpenStreetMap contributors'
    }).addTo(map);

    // Data lokasi personel dari PHP
    const locationsData = <?= json_encode($locations) ?>;

    // Custom Icon Marker Pesawat TNI AU
    const tniIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -30]
    });

    locationsData.forEach(function(member) {
        if (member.latitude && member.longitude) {
            const popupContent = `
                <div class="custom-popup" style="min-width: 220px; font-size: 13px;">
                    <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&size=36&background=0d6efd&color=fff" class="rounded-circle me-2" alt="Foto">
                        <div>
                            <strong class="text-primary d-block">${member.name}</strong>
                            <span class="badge bg-secondary" style="font-size: 10px;">${member.rank}</span>
                        </div>
                    </div>
                    <div class="text-muted" style="line-height: 1.5;">
                        <div><strong>NRP:</strong> ${member.nrp_nia}</div>
                        <div><strong>Satuan:</strong> ${member.unit}</div>
                        <div><strong>Penempatan:</strong> <span class="badge bg-primary text-white">${member.location_name || '-'}</span></div>
                        <div class="mt-1"><strong>Kontak:</strong> <a href="tel:${member.phone || ''}" class="text-primary text-decoration-none">${member.phone || '-'}</a></div>
                    </div>
                </div>
            `;

            L.marker([parseFloat(member.latitude), parseFloat(member.longitude)], { icon: tniIcon })
                .addTo(map)
                .bindPopup(popupContent);
        }
    });
</script>
