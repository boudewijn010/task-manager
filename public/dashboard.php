<?php
session_start();

// Redirect to login if not authenticated
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Simple DB connection to show some basic info (optional)
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "gemeente-app-db";

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // ignore DB errors for the simple dashboard
    $db = null;
}

// Attempt to show number of complaints (if table exists)
$complaintCount = null;
$openCount = null;
$completedCount = null;
$complaints = [];

if ($db) {
    try {
        // Total complaints
        $res = $db->query("SELECT COUNT(*) AS cnt FROM complaints");
        $complaintCount = $res->fetch()['cnt'] ?? null;
        
        // Open complaints (pending or in_progress)
        $res = $db->query("SELECT COUNT(*) AS cnt FROM complaints WHERE status IN ('pending', 'open', 'in_progress')");
        $openCount = $res->fetch()['cnt'] ?? null;
        
        // Completed complaints
        $res = $db->query("SELECT COUNT(*) AS cnt FROM complaints WHERE status = 'completed'");
        $completedCount = $res->fetch()['cnt'] ?? null;
        
        // Get all complaints with location data for map
        $res = $db->query("SELECT id, title, description, status, latitude, longitude, created_at FROM complaints WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
        $complaints = $res->fetchAll();
    } catch (Exception $e) {
        $complaintCount = null;
        $openCount = null;
        $completedCount = null;
        $complaints = [];
    }
}

$username = $_SESSION['user_email'] ?? 'Gebruiker';
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Leaflet CSS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .stat-card i {
            font-size: 3rem;
            opacity: 0.8;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .quick-action-btn {
            border-radius: 10px; 
            padding: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .quick-action-btn:hover {
            transform: scale(1.05);
        }
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .list-group-item {
            border: none;
            border-radius: 10px !important;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .welcome-banner {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        #map {
            height: 500px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .map-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<!-- Main Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/dashboard.php">
            <i class="fas fa-home"></i> Task Manager
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="/tasks">
                        <i class="fas fa-exclamation-circle"></i> Klachten
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/create-melding.php">
                        <i class="fas fa-plus-circle"></i> Nieuwe Melding
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">

                        <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt"></i> Uitloggen</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container mt-4">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-2"><i class="fas fa-hand-wave text-warning"></i> Welkom terug, <?= htmlspecialchars(explode('@', $username)[0]) ?>!</h2>
                <p class="text-muted mb-0">Hier is een overzicht van je klachten.</p>
            </div>
            <div class="col-md-4 text-end">
                <p class="mb-0 text-muted"><i class="fas fa-calendar"></i> <?= date('l, d F Y') ?></p>
                <p class="mb-0 text-muted"><i class="fas fa-clock"></i> <span id="live-clock"><?= date('H:i:s') ?></span></p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase mb-1" style="font-size: 0.9rem; opacity: 0.9;">Totaal Klachten</div>
                        <div class="stat-number"><?= $complaintCount ?? '0' ?></div>
                    </div>
                    <i class="fas fa-exclamation-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase mb-1" style="font-size: 0.9rem; opacity: 0.9;">Open</div>
                        <div class="stat-number"><?= $openCount ?? '0' ?></div>
                    </div>
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase mb-1" style="font-size: 0.9rem; opacity: 0.9;">Afgerond</div>
                        <div class="stat-number"><?= $completedCount ?? '0' ?></div>
                    </div>
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="map-container">
                <h5 class="mb-3"><i class="fas fa-map-marked-alt"></i> Kaart van Klachten</h5>
                <div id="map"></div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <!-- Complaints Overview Card -->
        <div class="col-md-8 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Klachten Overzicht</h5>
                </div>
                <div class="card-body">
                    <?php if ($complaintCount !== null): ?>
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i> Je hebt <strong><?= (int) $complaintCount ?></strong> totale klachten
                        </div>
                    <?php endif; ?>
                    <div class="list-group mb-3">
                        <a href="/complaints?status=pending" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-clock text-warning"></i> Open klachten</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="/complaints?status=in_progress" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-spinner text-info"></i> In behandeling</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="/complaints?status=completed" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-check-circle text-success"></i> Afgerond</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="/create-melding.php" class="btn btn-primary quick-action-btn">
                            <i class="fas fa-plus-circle"></i> Nieuwe Klacht Maken
                        </a>
                        <a href="/tasks" class="btn btn-outline-primary quick-action-btn">
                            <i class="fas fa-list"></i> Alle Klachten Bekijken
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card dashboard-card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Snelle Acties</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 col-sm-6 mb-3">
                            <a href="/create-melding.php" class="btn btn-outline-primary w-100 quick-action-btn">
                                <i class="fas fa-plus-circle fa-2x mb-2 d-block"></i>
                                Nieuwe Melding
                            </a>
                        </div>
                        <div class="col-md-6 col-sm-6 mb-3">
                            <a href="/tasks" class="btn btn-outline-info w-100 quick-action-btn">
                                <i class="fas fa-tasks fa-2x mb-2 d-block"></i>
                                Bekijk Taken
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="container mt-5 pb-4">
    <div class="text-center text-white">
        <p class="mb-0">&copy; <?= date('Y') ?> Task Manager. Alle rechten voorbehouden.</p>
    </div>
</footer>

<!-- Bootstrap Bundle with Popper for dropdowns -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Leaflet JS for maps -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Live Clock Script -->
<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const timeString = `${hours}:${minutes}:${seconds}`;
        
        const clockElement = document.getElementById('live-clock');
        if (clockElement) {
            clockElement.textContent = timeString;
        }
    }
    
    // Update clock immediately
    updateClock();
    
    // Update clock every second
    setInterval(updateClock, 1000);
</script>

<!-- Map Initialization Script -->
<script>
    // Initialize the map centered on Rotterdam
    const map = L.map('map').setView([51.9225, 4.47917], 11);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);
    
    // Rotterdam municipality boundary (detailed including Hoek van Holland)
    const rotterdamBoundary = [
        // Hoek van Holland (west)
        [51.9775, 4.1167], [51.9820, 4.1200], [51.9850, 4.1250], [51.9880, 4.1320],
        [51.9900, 4.1400], [51.9920, 4.1500], [51.9930, 4.1600], [51.9935, 4.1700],
        // Maassluis grens noord
        [51.9940, 4.1800], [51.9945, 4.1950], [51.9950, 4.2100], [51.9955, 4.2250],
        [51.9960, 4.2400], [51.9965, 4.2550], [51.9970, 4.2700],
        // Vlaardingen grens
        [51.9975, 4.2850], [51.9980, 4.3000], [51.9985, 4.3150], [51.9990, 4.3300],
        [51.9995, 4.3450], [52.0000, 4.3600], [52.0005, 4.3750],
        // Schiedam grens noord
        [52.0010, 4.3900], [52.0015, 4.4050], [52.0020, 4.4200], [52.0025, 4.4350],
        [52.0030, 4.4500], [52.0035, 4.4650],
        // Noord kant (Hillegersberg, Overschie)
        [52.0040, 4.4800], [52.0042, 4.4950], [52.0044, 4.5100], [52.0045, 4.5250],
        [52.0046, 4.5400], [52.0047, 4.5550], [52.0048, 4.5700],
        // Capelle grens noord-oost
        [52.0048, 4.5850], [52.0047, 4.6000], [52.0045, 4.6150], [52.0042, 4.6300],
        [52.0038, 4.6450], [52.0033, 4.6600], [52.0027, 4.6750],
        // Krimpen grens oost (Prins Alexander)
        [52.0020, 4.6900], [52.0012, 4.7050], [52.0003, 4.7200], [51.9993, 4.7350],
        [51.9982, 4.7500], [51.9970, 4.7650], [51.9957, 4.7800],
        // Zuidoost (Nesselande richting)
        [51.9943, 4.7950], [51.9928, 4.8100], [51.9912, 4.8250], [51.9895, 4.8400],
        // Oost kant (richting Nieuwerkerk)
        [51.9877, 4.8550], [51.9858, 4.8700], [51.9838, 4.8850], [51.9817, 4.9000],
        // Zuid-oost (Zevenhuizen grens)
        [51.9795, 4.9100], [51.9772, 4.9180], [51.9748, 4.9240], [51.9723, 4.9280],
        [51.9697, 4.9300], [51.9670, 4.9300], [51.9642, 4.9280],
        // Zuid (Ridderkerk, Barendrecht grens)
        [51.9613, 4.9240], [51.9583, 4.9180], [51.9552, 4.9100], [51.9520, 4.9000],
        [51.9487, 4.8880], [51.9453, 4.8740], [51.9418, 4.8580],
        // Zuid Charlois, Hoogvliet
        [51.9382, 4.8400], [51.9345, 4.8200], [51.9307, 4.8000], [51.9268, 4.7780],
        [51.9228, 4.7540], [51.9187, 4.7280], [51.9145, 4.7000],
        // Botlek, Europoort (zuid)
        [51.9102, 4.6700], [51.9058, 4.6380], [51.9013, 4.6040], [51.8967, 4.5680],
        [51.8920, 4.5300], [51.8872, 4.4900], [51.8823, 4.4480],
        // Maasvlakte (zuid-west)
        [51.8773, 4.4040], [51.8722, 4.3580], [51.8670, 4.3100], [51.8617, 4.2600],
        [51.8563, 4.2080], [51.8508, 4.1540], [51.8452, 4.0980],
        // West kant Maasvlakte
        [51.8450, 4.0500], [51.8500, 4.0200], [51.8550, 4.0000], [51.8620, 3.9850],
        [51.8700, 3.9750], [51.8790, 3.9700], [51.8890, 3.9700],
        // Hoek van Holland (terug naar start)
        [51.9000, 3.9750], [51.9100, 3.9850], [51.9200, 4.0000], [51.9300, 4.0200],
        [51.9400, 4.0400], [51.9500, 4.0600], [51.9600, 4.0800], [51.9700, 4.1000],
        [51.9775, 4.1167]
    ];
    
    // Add Rotterdam boundary to map
    L.polygon(rotterdamBoundary, {
        color: '#667eea',
        weight: 3,
        fillColor: '#667eea',
        fillOpacity: 0.1,
        dashArray: '5, 5'
    }).addTo(map).bindPopup('<strong>Gemeente Rotterdam</strong>');
    
    // Complaint data from PHP
    const complaints = <?= json_encode($complaints) ?>;
    
    // Function to get marker color based on status
    function getMarkerIcon(status) {
        let color = '#3388ff'; // default blue
        
        if (status === 'completed') {
            color = '#43e97b'; // green
        } else if (status === 'in_progress') {
            color = '#f093fb'; // pink
        } else if (status === 'pending' || status === 'open') {
            color = '#f5576c'; // red
        }
        
        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="background-color: ${color}; width: 25px; height: 25px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
            iconSize: [25, 25],
            iconAnchor: [12, 12]
        });
    }
    
    // Function to get status label
    function getStatusLabel(status) {
        const labels = {
            'pending': 'Open',
            'open': 'Open',
            'in_progress': 'In behandeling',
            'completed': 'Afgerond'
        };
        return labels[status] || status;
    }
    
    // Add markers for each complaint
    if (complaints && complaints.length > 0) {
        complaints.forEach(complaint => {
            if (complaint.latitude && complaint.longitude) {
                const marker = L.marker(
                    [parseFloat(complaint.latitude), parseFloat(complaint.longitude)],
                    { icon: getMarkerIcon(complaint.status) }
                ).addTo(map);
                
                // Create popup content
                const popupContent = `
                    <div style="min-width: 200px;">
                        <h6 style="margin-bottom: 10px; color: #333;">${complaint.title || 'Geen titel'}</h6>
                        <p style="margin-bottom: 5px; font-size: 0.9em;">${complaint.description ? complaint.description.substring(0, 100) + '...' : 'Geen beschrijving'}</p>
                        <p style="margin-bottom: 5px;"><strong>Status:</strong> <span style="color: ${complaint.status === 'completed' ? '#43e97b' : '#f5576c'};">${getStatusLabel(complaint.status)}</span></p>
                        <p style="margin-bottom: 0; font-size: 0.85em; color: #666;"><strong>Gemeld op:</strong> ${new Date(complaint.created_at).toLocaleDateString('nl-NL')}</p>
                        <a href="/complaint-detail.php?id=${complaint.id}" style="display: inline-block; margin-top: 10px; padding: 5px 10px; background-color: #667eea; color: white; text-decoration: none; border-radius: 5px; font-size: 0.85em;">Bekijk details</a>
                    </div>
                `;
                
                marker.bindPopup(popupContent);
            }
        });
        
        // Fit map to show all markers if there are any
        if (complaints.length > 0) {
            const group = new L.featureGroup(
                complaints
                    .filter(c => c.latitude && c.longitude)
                    .map(c => L.marker([parseFloat(c.latitude), parseFloat(c.longitude)]))
            );
            map.fitBounds(group.getBounds().pad(0.1));
        }
    } else {
        // Show message if no complaints with location - centered on Rotterdam
        const popup = L.popup()
            .setLatLng([51.9225, 4.47917])
            .setContent('<p>Geen klachten met locatie gevonden.</p>')
            .openOn(map);
    }
</script>
</body>
</html>