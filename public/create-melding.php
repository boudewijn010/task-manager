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

    if ($title && $description && $contact_email) {
        try {
            $stmt = $db->prepare('INSERT INTO complaints (title, description, type, priority, contact_email, address, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "new")');
            $stmt->execute([$title, $description, $type, $priority, $contact_email, $address, $latitude, $longitude]);
            
            $success = "Uw melding is succesvol ingediend. We nemen contact met u op via het opgegeven e-mailadres.";
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

                        <form method="POST" action="/create-melding.php">
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
                                <label class="form-label">Locatie (klik op de kaart om te selecteren)</label>
                                <div id="map" style="height:300px;border:1px solid #ddd;"></div>
                                <input type="hidden" id="latitude" name="latitude">
                                <input type="hidden" id="longitude" name="longitude">
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
        // Initialize map
        var map = L.map('map').setView([52.370216, 4.895168], 12); // default to Amsterdam
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var marker;
        function setMarker(lat, lng) {
            if (marker) map.removeLayer(marker);
            marker = L.marker([lat, lng]).addTo(map);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        }

        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
        });
    </script>
</body>
</html>