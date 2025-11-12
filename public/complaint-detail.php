<?php
session_start();

// Database connection
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
    die("Database connectie mislukt: " . $e->getMessage());
}

// Create notes table if it doesn't exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS complaint_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        complaint_id INT NOT NULL,
        user_email VARCHAR(255) NOT NULL,
        note TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (Exception $e) {
    // Table might already exist
}

// Get complaint ID from URL
$complaintId = $_GET['id'] ?? null;

if (!$complaintId) {
    header('Location: /index.php');
    exit;
}

// Handle note submission
$noteSuccess = false;
$noteError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_note'])) {
    // Check if user is logged in (admin only)
    if (empty($_SESSION['user_id'])) {
        $noteError = "Je moet ingelogd zijn als admin om een notitie te plaatsen.";
    } else {
        $note = trim($_POST['note'] ?? '');
        $userEmail = $_SESSION['user_email'] ?? 'admin';
        
        if ($note) {
            try {
                $stmt = $db->prepare("INSERT INTO complaint_notes (complaint_id, user_email, note) VALUES (?, ?, ?)");
                $stmt->execute([$complaintId, $userEmail, $note]);
                $noteSuccess = true;
                // Redirect to avoid form resubmission
                header("Location: /complaint-detail.php?id={$complaintId}&success=1");
                exit;
            } catch (Exception $e) {
                $noteError = "Fout bij het plaatsen van de notitie: " . $e->getMessage();
            }
        } else {
            $noteError = "Vul de notitie in.";
        }
    }
}

// Check if note was just added
if (isset($_GET['success'])) {
    $noteSuccess = true;
}

// Fetch complaint details
try {
    $stmt = $db->prepare("SELECT * FROM complaints WHERE id = ?");
    $stmt->execute([$complaintId]);
    $complaint = $stmt->fetch();
    
    if (!$complaint) {
        header('Location: /index.php');
        exit;
    }
} catch (Exception $e) {
    die("Fout bij het ophalen van klacht: " . $e->getMessage());
}

// Fetch notes for this complaint
try {
    $stmt = $db->prepare("SELECT * FROM complaint_notes WHERE complaint_id = ? ORDER BY created_at DESC");
    $stmt->execute([$complaintId]);
    $notes = $stmt->fetchAll();
} catch (Exception $e) {
    $notes = [];
}

// Helper function to get status label in Dutch
function getStatusLabel($status) {
    $labels = [
        'new' => 'Nieuw',
        'pending' => 'Open',
        'open' => 'Open',
        'in_progress' => 'In behandeling',
        'resolved' => 'Opgelost',
        'completed' => 'Afgerond',
        'closed' => 'Gesloten'
    ];
    return $labels[$status] ?? $status;
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    $classes = [
        'new' => 'bg-info',
        'pending' => 'bg-warning',
        'open' => 'bg-warning',
        'in_progress' => 'bg-primary',
        'resolved' => 'bg-success',
        'completed' => 'bg-success',
        'closed' => 'bg-secondary'
    ];
    return $classes[$status] ?? 'bg-secondary';
}

// Helper function to get priority badge class
function getPriorityBadgeClass($priority) {
    $classes = [
        'low' => 'bg-secondary',
        'medium' => 'bg-warning',
        'high' => 'bg-danger'
    ];
    return $classes[$priority] ?? 'bg-secondary';
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Klacht Details - <?= htmlspecialchars($complaint['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 50px;
        }
        .detail-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        .comment-card {
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .comment-card:hover {
            transform: translateX(5px);
        }
        #map {
            height: 300px;
            border-radius: 10px;
            margin-top: 15px;
        }
        .photo-preview {
            max-width: 100%;
            border-radius: 10px;
            margin-top: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/index.php">
            <i class="fas fa-home"></i> Gemeente App
        </a>
        <div class="navbar-nav ms-auto">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="nav-link" href="/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            <?php else: ?>
                <a class="nav-link" href="/login.php">
                    <i class="fas fa-sign-in-alt"></i> Inloggen
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Back Button -->
    <div class="mt-3">
        <a href="/index.php" class="btn btn-light">
            <i class="fas fa-arrow-left"></i> Terug naar overzicht
        </a>
    </div>

    <!-- Complaint Details -->
    <div class="card detail-card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($complaint['title']) ?></h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <!-- Status and Priority -->
                    <div class="mb-3">
                        <span class="badge <?= getStatusBadgeClass($complaint['status']) ?> me-2">
                            <i class="fas fa-info-circle"></i> <?= getStatusLabel($complaint['status']) ?>
                        </span>
                        <span class="badge <?= getPriorityBadgeClass($complaint['priority']) ?>">
                            <i class="fas fa-exclamation"></i> Prioriteit: <?= ucfirst($complaint['priority']) ?>
                        </span>
                        <span class="badge bg-secondary ms-2">
                            <i class="fas fa-tag"></i> <?= ucfirst($complaint['type']) ?>
                        </span>
                    </div>

                    <!-- Description -->
                    <h5>Beschrijving</h5>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>

                    <!-- Contact Information -->
                    <h5 class="mt-4">Contact Informatie</h5>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($complaint['contact_email']) ?></p>
                    
                    <?php if ($complaint['address']): ?>
                        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($complaint['address']) ?></p>
                    <?php endif; ?>

                    <!-- Timestamps -->
                    <div class="mt-4">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> Aangemaakt: <?= date('d-m-Y H:i', strtotime($complaint['created_at'])) ?>
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-sync"></i> Laatst bijgewerkt: <?= date('d-m-Y H:i', strtotime($complaint['updated_at'])) ?>
                        </small>
                    </div>

                    <!-- Photo -->
                    <?php if (!empty($complaint['photo_path'])): ?>
                        <h5 class="mt-4">Foto</h5>
                        <img src="<?= htmlspecialchars($complaint['photo_path']) ?>" alt="Complaint photo" class="photo-preview">
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <!-- Map -->
                    <?php if ($complaint['latitude'] && $complaint['longitude']): ?>
                        <h5>Locatie</h5>
                        <div id="map"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Section -->
    <div class="card detail-card" id="comments">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Notities (<?= count($notes) ?>)</h5>
        </div>
        <div class="card-body">
            <!-- Success Message -->
            <?php if ($noteSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Je notitie is succesvol geplaatst!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($noteError): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($noteError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Note Form -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="card mb-4" style="background-color: #f8f9fa;">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-pen"></i> Plaats een notitie (Admin)</h6>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="note" class="form-label">Notitie *</label>
                                <textarea class="form-control" id="note" name="note" rows="4" required 
                                          placeholder="Schrijf je notitie hier..."></textarea>
                            </div>
                            <button type="submit" name="submit_note" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Notitie Plaatsen
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-lock"></i> Je moet ingelogd zijn als admin om een notitie te plaatsen. 
                    <a href="/login.php" class="alert-link">Inloggen</a>
                </div>
            <?php endif; ?>

            <!-- Display Notes -->
            <?php if (count($notes) > 0): ?>
                <h6 class="mb-3">Alle notities</h6>
                <?php foreach ($notes as $note): ?>
                    <div class="card comment-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-subtitle mb-2">
                                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($note['user_email']) ?>
                                    </h6>
                                    <p class="card-text mb-1"><?= nl2br(htmlspecialchars($note['note'])) ?></p>
                                </div>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> <?= date('d-m-Y H:i', strtotime($note['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Er zijn nog geen notities geplaatst. Wees de eerste!
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Leaflet JS -->
<?php if ($complaint['latitude'] && $complaint['longitude']): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Initialize map
    const map = L.map('map').setView([<?= $complaint['latitude'] ?>, <?= $complaint['longitude'] ?>], 15);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);
    
    // Add marker
    L.marker([<?= $complaint['latitude'] ?>, <?= $complaint['longitude'] ?>]).addTo(map)
        .bindPopup('<?= htmlspecialchars($complaint['title']) ?>')
        .openPopup();
</script>
<?php endif; ?>

</body>
</html>
