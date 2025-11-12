<?php


$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "gemeente-app-db";

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Connectie mislukt: " . $e->getMessage());
}


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

function getTasks($db, $status = null)
{
    if ($status) {
        $stmt = $db->prepare('SELECT * FROM tasks WHERE status = ? ORDER BY created_at DESC');
        $stmt->execute([$status]);
    } else {
        $stmt = $db->query('SELECT * FROM tasks ORDER BY created_at DESC');
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function renderView($view, $data = [])
{
    extract($data);
    ob_start();
    include __DIR__ . "/../resources/views/$view";
    return ob_get_clean();
}

function getTask($db, $id)
{
    $stmt = $db->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

try {
    // Handle different routes
    if ($path === '/') {
        // Show landing page
        ?>
        <!DOCTYPE html>
        <html lang="nl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Welkom bij Task Manager</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                .hero-section {
                    background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
                    color: white;
                    padding: 100px 0;
                }
                .feature-icon {
                    font-size: 3rem;
                    margin-bottom: 1rem;
                    color: #0d6efd;
                }
            </style>
        </head>
        <body>
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

            <!-- Hero Section -->
            <div class="hero-section">
                <div class="container text-center">
                    <h1 class="display-4 mb-4">Welkom bij Task Manager</h1>
                    <p class="lead mb-4">Beheer uw taken en klachten op één centrale plek</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="/create-melding.php" class="btn btn-light btn-lg px-4">
                            <i class="fas fa-plus-circle"></i> Nieuwe Melding
                        </a>
                        <a href="/login.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-sign-in-alt"></i> Inloggen
                        </a>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="container py-5">
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3>Taakbeheer</h3>
                        <p>Houd al uw taken overzichtelijk bij en volg de voortgang</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <h3>Klachtenbeheer</h3>
                        <p>Registreer en volg klachten efficiënt op</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Voortgang Monitoren</h3>
                        <p>Bekijk statistieken en voortgang in één oogopslag</p>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="bg-light py-5">
                <div class="container text-center">
                    <h2>Klaar om te beginnen?</h2>
                    <p class="lead mb-4">Log in om uw taken en klachten te beheren</p>
                    <a href="/login.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Inloggen
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
    } elseif ($path === '/tasks') {
        // Check if user is logged in
        session_start();
        if (empty($_SESSION['user_id'])) {
            header('Location: /login.php');
            exit;
        }
        
        // Show task list for logged in users
        $status = $_GET['status'] ?? null;
        $tasks = getTasks($db, $status);

        // Also load complaints so they can be managed from the /tasks page
        try {
            // Create notes table if it doesn't exist
            $db->exec("CREATE TABLE IF NOT EXISTS complaint_notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                complaint_id INT NOT NULL,
                user_email VARCHAR(255) NOT NULL,
                note TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            // Get complaints with note count
            $stmt = $db->query('
                SELECT c.*, COUNT(cn.id) as note_count 
                FROM complaints c 
                LEFT JOIN complaint_notes cn ON c.id = cn.complaint_id 
                GROUP BY c.id 
                ORDER BY c.created_at DESC
            ');
            $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $complaints = [];
        }

        ?>
        <!DOCTYPE html>
        <html lang="nl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Task Manager - Taken</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        </head>
        <body>
            <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
                <div class="container">
                    <a class="navbar-brand" href="/dashboard.php">
                        <i class="fas fa-tasks"></i> Task Manager
                    </a>
                    <div class="navbar-nav">
                        <a class="nav-link" href="/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a class="nav-link" href="/tasks">
                            <i class="fas fa-list"></i> Taken
                        </a>
                    </div>
                    <div class="navbar-nav ms-auto">
                        <a class="nav-link" href="/login.php?logout=1">
                            <i class="fas fa-sign-out-alt"></i> Uitloggen
                        </a>
                    </div>
                </div>
            </nav>
             <!-- DIT zIJN DE Klachten  -->
                <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
                    <h2><i class="fas fa-exclamation-circle text-danger"></i> Klachten</h2>
                </div>

                <?php if (!empty($complaints)): ?>
                    <div class="row">
                        <?php foreach ($complaints as $c): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title"><?= htmlspecialchars($c['title']) ?></h5>
                                            <span class="badge bg-<?= $c['priority'] == 'high' ? 'danger' : ($c['priority'] == 'medium' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($c['priority']) ?>
                                            </span>
                                        </div>

                                        <p class="card-text text-muted"><?= htmlspecialchars(substr($c['description'] ?? '', 0, 120)) ?></p>

                                        <?php if (!empty($c['photo_path'])): ?>
                                            <div class="mb-3">
                                                <img src="/<?= htmlspecialchars($c['photo_path']) ?>" alt="Melding foto" class="img-fluid rounded" style="max-height: 200px; width: auto;">
                                            </div>
                                        <?php endif; ?>

                                        <div class="mb-3">
                                            <span class="badge bg-<?= $c['status'] == 'resolved' ? 'success' : ($c['status'] == 'in_progress' ? 'info' : 'danger') ?>">
                                                <?= ucfirst(str_replace('_', ' ', $c['status'])) ?>
                                            </span>
                                        </div>

                                        <p class="card-text"><small class="text-muted">Contact: <?= htmlspecialchars($c['contact_email']) ?></small></p>

                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <a href="/complaint-detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i> Bekijken
                                                </a>
                                                <a href="/complaint-detail.php?id=<?= $c['id'] ?>#comments" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-sticky-note"></i> Notities
                                                    <?php if (isset($c['note_count']) && $c['note_count'] > 0): ?>
                                                        <span class="badge bg-danger"><?= $c['note_count'] ?></span>
                                                    <?php endif; ?>
                                                </a>
                                            </div>

                                            <div>
                                                <?php if ($c['status'] !== 'resolved'): ?>
                                                    <a href="/complaints/<?= $c['id'] ?>/complete" class="btn btn-sm btn-success" onclick="return confirm('Markeer deze klacht als afgehandeld?')">
                                                        <i class="fas fa-check"></i> Afhandelen
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-success">Afgehandeld</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-muted">
                                        <small>Ingediend <?= date('M d, Y', strtotime($c['created_at'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-circle fa-4x text-muted mb-3"></i>
                        <h4>Geen klachten gevonden</h4>
                        <p class="text-muted">Er zijn momenteel geen geregistreerde klachten.</p>
                    </div>
                <?php endif; ?>
            </div>
        </body>

        </html>
        <?php

    } elseif (preg_match('/^\/tasks\/(\d+)\/complete$/', $path, $matches)) {
        // Complete task
        $id = $matches[1];
        $stmt = $db->prepare('UPDATE tasks SET status = "completed" WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: /');
        exit;

    } elseif (preg_match('/^\/tasks\/(\d+)\/delete$/', $path, $matches)) {
        // Delete task
        $id = $matches[1];
        $stmt = $db->prepare('DELETE FROM tasks WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: /');
        exit;

    } elseif (preg_match('/^\/complaints\/(\d+)\/complete$/', $path, $matches)) {
        // Mark complaint as resolved
        $id = $matches[1];
        try {
            $stmt = $db->prepare("UPDATE complaints SET status = 'resolved' WHERE id = ?");
            $stmt->execute([$id]);
        } catch (Exception $e) {
            // ignore
        }
        header('Location: /tasks');
        exit;
    } elseif (preg_match('/^\/complaints\/(\d+)$/', $path, $matches)) {
        // Show single complaint with map (if location available)
        $id = (int) $matches[1];
        try {
            $stmt = $db->prepare('SELECT * FROM complaints WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $complaint = false;
        }

        if (!$complaint) {
            http_response_code(404);
            echo "<h1>Klacht niet gevonden</h1>";
            exit;
        }

        ?>
        <!DOCTYPE html>
        <html lang="nl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Klacht #<?= htmlspecialchars($complaint['id']) ?> - Task Manager</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <style>#complaint-map { height: 400px; }</style>
        </head>
        <body class="bg-light">
            <div class="container py-4">
                <a href="/tasks" class="btn btn-link">← Terug naar taken</a>
                <div class="card mt-3">
                    <div class="card-header">
                        <h4><?= htmlspecialchars($complaint['title']) ?></h4>
                    </div>
                    <div class="card-body">
                        <p><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                        <p><strong>Type:</strong> <?= htmlspecialchars($complaint['type']) ?> • <strong>Prioriteit:</strong> <?= htmlspecialchars($complaint['priority']) ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($complaint['contact_email']) ?></p>
                        <?php if (!empty($complaint['address'])): ?>
                            <p><strong>Adres:</strong> <?= htmlspecialchars($complaint['address']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($complaint['latitude']) && !empty($complaint['longitude'])): ?>
                            <div id="complaint-map"></div>
                        <?php else: ?>
                            <div class="alert alert-secondary">Geen locatie opgeslagen voor deze klacht.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <?php if (!empty($complaint['latitude']) && !empty($complaint['longitude'])): ?>
            <script>
                var map = L.map('complaint-map').setView([<?= (float)$complaint['latitude'] ?>, <?= (float)$complaint['longitude'] ?>], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
                L.marker([<?= (float)$complaint['latitude'] ?>, <?= (float)$complaint['longitude'] ?>]).addTo(map);
            </script>
            <?php endif; ?>
        </body>
        </html>
        <?php
        exit;
    

    } else {
        // 404 for other routes
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
    }

} catch (Exception $e) {
    echo "<h1>Error: " . htmlspecialchars($e->getMessage()) . "</h1>";
}