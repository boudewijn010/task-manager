<?php
session_start();


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
    die("Connectie mislukt: " . $e->getMessage());
}

$error = '';
$submittedEmail = '';

// Als de gebruiker op "Uitloggen" klikt
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /");
    exit;
}

// Loginverwerking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedEmail = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$submittedEmail || !$password) {
        $error = 'Vul email en wachtwoord in.';
    } else {
        // Test account for development
        if ($submittedEmail === 'admin@example.com' && $password === 'admin123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['user_email'] = $submittedEmail;
            header('Location: /dashboard.php');
            exit;
        }

        // Check of de tabel bestaat
        $usersTableExists = false;
        try {
            $res = $db->query("SHOW TABLES LIKE 'gebruikers'");
            $usersTableExists = ($res && $res->rowCount() > 0);
        } catch (Exception $e) {
            $usersTableExists = false;
        }

        $userRow = false;
        if ($usersTableExists) {
            try {
                $stmt = $db->prepare('SELECT id, email, password FROM gebruikers WHERE email = ? LIMIT 1');
                $stmt->execute([$submittedEmail]);
                $userRow = $stmt->fetch();
            } catch (Exception $e) {
                $userRow = false;
            }
        }

        if ($userRow && password_verify($password, $userRow['password'])) {
            $_SESSION['user_id'] = $userRow['id'];
            $_SESSION['user_email'] = $userRow['email'];
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Ongeldige inloggegevens. Gebruik de test account gegevens onderaan de pagina.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen - Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 40px auto;
        }
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-tasks"></i> Task Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/create-melding.php">
                    <i class="fas fa-plus-circle"></i> Nieuwe Melding
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-sign-in-alt"></i> Inloggen
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/login.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mailadres</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                    value="<?= htmlspecialchars($submittedEmail) ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Wachtwoord</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Inloggen
                            </button>
                            <a href="/" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Terug naar home
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light text-center py-3">
                    <div class="text-muted">
                        <small>
                            <strong>Test inloggegevens:</strong><br>
                            Email: admin@example.com<br>
                            Wachtwoord: admin123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>