<?php
// Database connection (reuse the connection code from index.php)
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
    
    // Include table creation script
    require_once 'create-complaints-table.php';
    
} catch (PDOException $e) {
    die("Database connectie mislukt: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? 'klacht';
    $priority = $_POST['priority'] ?? 'medium';
    $contact_email = $_POST['contact_email'] ?? '';
    $address = $_POST['address'] ?? null;
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $photo_path = null;

    if ($title && $description && $contact_email && $latitude && $longitude) {
        try {
            // Add photo_path column if it doesn't exist
            try {
                $db->exec("ALTER TABLE complaints ADD COLUMN photo_path VARCHAR(255) NULL");
            } catch (Exception $e) {
                // Column might already exist, ignore error
            }

            // Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/';
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $maxFileSize = 5 * 1024 * 1024; // 5MB
                
                $fileType = $_FILES['photo']['type'];
                $fileSize = $_FILES['photo']['size'];
                
                if (in_array($fileType, $allowedTypes) && $fileSize <= $maxFileSize) {
                    $fileExtension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $newFileName = uniqid('photo_', true) . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                        $photo_path = 'uploads/' . $newFileName;
                    }
                } else {
                    $error = "Ongeldige foto. Alleen JPG, PNG of GIF tot 5MB toegestaan.";
                }
            }

            if (!isset($error)) {
                $stmt = $db->prepare('INSERT INTO complaints (title, description, type, priority, contact_email, address, latitude, longitude, photo_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "new")');
                $stmt->execute([$title, $description, $type, $priority, $contact_email, $address, $latitude, $longitude, $photo_path]);
                
                $success = "Uw melding is succesvol ingediend. We nemen contact met u op via het opgegeven e-mailadres.";
            }
        } catch (Exception $e) {
            $error = "Er is een fout opgetreden bij het indienen van uw melding. Probeer het later opnieuw.";
        }
    } else {
        $error = "Vul alle verplichte velden in.";
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuwe Melding - Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-tasks"></i> Task Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/login.php">
                    <i class="fas fa-sign-in-alt"></i> Inloggen
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Nieuwe Melding</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/create-melding.php" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Onderwerp *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label">Type Melding</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="klacht">Klacht</option>
                                    <option value="suggestie">Suggestie</option>
                                    <option value="vraag">Vraag</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Beschrijving *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                                <div class="form-text">Beschrijf uw melding zo duidelijk mogelijk.</div>
                            </div>

                            <div class="mb-3">
                                <label for="photo" class="form-label">Foto (optioneel)</label>
                                <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/jpg,image/png,image/gif">
                                <div class="form-text">Upload een foto van de situatie (max 5MB, JPG/PNG/GIF)</div>
                            </div>

                            <div class="mb-3">
                                <label for="priority" class="form-label">Prioriteit</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low">Laag</option>
                                    <option value="medium" selected>Gemiddeld</option>
                                    <option value="high">Hoog</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="contact_email" class="form-label">E-mailadres *</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                                <div class="form-text">We gebruiken dit om contact met u op te nemen over uw melding.</div>
                            </div>

                            <!-- Location picker -->
                            <div class="mb-3">
                                <label class="form-label">Locatie (klik op de kaart om te selecteren) *</label>
                                <div id="map" style="height:300px;border:1px solid #ddd;"></div>
                                <input type="hidden" id="latitude" name="latitude" required>
                                <input type="hidden" id="longitude" name="longitude" required>
                                <small class="text-muted d-block mt-2">
                                    <span id="location-status" class="text-danger">
                                        <i class="fas fa-exclamation-circle"></i> Klik op de kaart om een locatie te selecteren
                                    </span>
                                </small>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Adres (optioneel)</label>
                                <input type="text" class="form-control" id="address" name="address" placeholder="Optioneel: beschrijving van de locatie">
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Terug
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Melding Indienen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet for map selection -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map centered on Rotterdam
        var map = L.map('map').setView([51.9225, 4.47917], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
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
        var rotterdamPolygon = L.polygon(rotterdamBoundary, {
            color: '#667eea',
            weight: 3,
            fillColor: '#667eea',
            fillOpacity: 0.1,
            dashArray: '5, 5'
        }).addTo(map);

        // Function to check if point is inside Rotterdam
        function isPointInRotterdam(lat, lng) {
            var point = L.latLng(lat, lng);
            var polygon = L.polygon(rotterdamBoundary);
            
            // Simple point-in-polygon check
            var x = lng, y = lat;
            var inside = false;
            for (var i = 0, j = rotterdamBoundary.length - 1; i < rotterdamBoundary.length; j = i++) {
                var xi = rotterdamBoundary[i][1], yi = rotterdamBoundary[i][0];
                var xj = rotterdamBoundary[j][1], yj = rotterdamBoundary[j][0];
                
                var intersect = ((yi > y) != (yj > y))
                    && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
                if (intersect) inside = !inside;
            }
            return inside;
        }

        var marker;
        function setMarker(lat, lng) {
            if (marker) map.removeLayer(marker);
            marker = L.marker([lat, lng]).addTo(map);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            
            // Update status indicator
            const statusElement = document.getElementById('location-status');
            statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Locatie geselecteerd in Rotterdam';
            statusElement.className = 'text-success';
        }

        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;
            
            // Check if click is within Rotterdam
            if (isPointInRotterdam(lat, lng)) {
                setMarker(lat, lng);
            } else {
                alert('Selecteer een locatie binnen de gemeente Rotterdam.');
            }
        });
        
        // Prevent form submission if location is not selected
        document.querySelector('form').addEventListener('submit', function(e) {
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            if (!latitude || !longitude) {
                e.preventDefault();
                alert('Selecteer eerst een locatie op de kaart door erop te klikken.');
                document.getElementById('map').scrollIntoView({ behavior: 'smooth' });
                return false;
            }
        });
    </script>
</body>
</html>