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

// Attempt to show number of tasks (if table exists)
$taskCount = null;
if ($db) {
    try {
        $res = $db->query("SELECT COUNT(*) AS cnt FROM tasks");
        $taskCount = $res->fetch()['cnt'] ?? null;
    } catch (Exception $e) {
        $taskCount = null;
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Main Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/dashboard.php">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="tasksDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-tasks"></i> Taken
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/tasks"><i class="fas fa-list"></i> Alle taken</a></li>
                        <li><a class="dropdown-item" href="/create-melding.php"><i class="fas fa-plus"></i> Nieuwe taak</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/tasks?status=pending"><i class="fas fa-clock"></i> Open taken</a></li>
                        <li><a class="dropdown-item" href="/tasks?status=completed"><i class="fas fa-check"></i> Afgeronde taken</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="complaintsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-exclamation-circle"></i> Klachten
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/complaints"><i class="fas fa-list"></i> Alle klachten</a></li>
                        <li><a class="dropdown-item" href="/complaints/create"><i class="fas fa-plus"></i> Nieuwe klacht</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/complaints?status=open"><i class="fas fa-envelope-open"></i> Open klachten</a></li>
                        <li><a class="dropdown-item" href="/complaints?status=closed"><i class="fas fa-envelope"></i> Afgehandelde klachten</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link"><i class="fas fa-user"></i> <?= htmlspecialchars($username) ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/login.php?logout=1"><i class="fas fa-sign-out-alt"></i> Uitloggen</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container mt-4">
    <div class="row">
        <!-- Tasks Overview Card -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-tasks"></i> Taken Overzicht
                </div>
                <div class="card-body">
                    <?php if ($taskCount !== null): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Totale taken</h5>
                            <span class="badge bg-primary rounded-pill"><?= (int) $taskCount ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="list-group">
                        <a href="/tasks?status=pending" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-clock text-warning"></i> Open taken</span>
                            <span class="badge bg-warning rounded-pill">...</span>
                        </a>
                        <a href="/tasks?status=in_progress" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-spinner text-info"></i> In behandeling</span>
                            <span class="badge bg-info rounded-pill">...</span>
                        </a>
                        <a href="/tasks?status=completed" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-check text-success"></i> Afgerond</span>
                            <span class="badge bg-success rounded-pill">...</span>
                        </a>
                    </div>
                    <div class="mt-3">
                        <a href="/create-melding.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nieuwe taak
                        </a>
                        <a href="/tasks" class="btn btn-outline-primary">
                            <i class="fas fa-list"></i> Alle taken
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Complaints Overview Card -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-exclamation-circle"></i> Klachten Overzicht
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="/complaints?status=new" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-exclamation text-danger"></i> Nieuwe klachten</span>
                            <span class="badge bg-danger rounded-pill">...</span>
                        </a>
                        <a href="/complaints?status=in_progress" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-spinner text-info"></i> In behandeling</span>
                            <span class="badge bg-info rounded-pill">...</span>
                        </a>
                        <a href="/complaints?status=resolved" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-check text-success"></i> Afgehandeld</span>
                            <span class="badge bg-success rounded-pill">...</span>
                        </a>
                    </div>
                    <div class="mt-3">
                        <a href="/complaints/create" class="btn btn-danger">
                            <i class="fas fa-plus"></i> Nieuwe klacht
                        </a>
                        <a href="/complaints" class="btn btn-outline-danger">
                            <i class="fas fa-list"></i> Alle klachten
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper for dropdowns -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
