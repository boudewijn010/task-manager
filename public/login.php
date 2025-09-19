<?php
session_start();

// DB settings - keep in sync with index.php
$host = "127.0.0.1";    // meestal localhost bij XAMPP
$user = "root";         // standaard XAMPP user
$pass = "";             // standaard leeg wachtwoord
$dbname = "gemeente-app-db";

// Maak PDO connectie (de rest van het bestand verwacht PDO via $db)
try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // echo "Connectie gelukt!";
} catch (PDOException $e) {
    // Friendly message; in productie log the real error instead
    die("Connectie mislukt: " . $e->getMessage());
}
// If already logged in, redirect
if (!empty($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

// CSRF token generation/validation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$error = '';
$submittedEmail = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedEmail = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Ongeldige CSRF-token.';
    } elseif (!$submittedEmail || !$password) {
        $error = 'Vul email en wachtwoord in.';
    } else {
        // Check if users table exists
        $usersTableExists = false;
        try {
            $res = $db->query("SHOW TABLES LIKE 'users'");
            $usersTableExists = ($res && $res->rowCount() > 0);
        } catch (Exception $e) {
            $usersTableExists = false;
        }

        $userRow = false;
        if ($usersTableExists) {
            try {
                $stmt = $db->prepare('SELECT id, email, password FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$submittedEmail]);
                $userRow = $stmt->fetch();
            } catch (Exception $e) {
                $userRow = false;
            }
        }

        if ($userRow) {
            if (password_verify($password, $userRow['password'])) {
                $_SESSION['user_id'] = $userRow['id'];
                $_SESSION['user_email'] = $userRow['email'];
                header('Location: /');
                exit;
            } else {
                $error = 'Ongeldig wachtwoord.';
            }
        } else {
            if (!$usersTableExists) {
                // Development fallback only when users table is missing
                if ($submittedEmail === 'root' && $password === '') {
                    $_SESSION['user_id'] = 0;
                    $_SESSION['user_email'] = 'root';
                    header('Location: /');
                    exit;
                }
                $error = 'Geen gebruikers gevonden in database; gebruik fallback (root / leeg) of maak users table aan.';
            } else {
                $error = 'Gebruiker niet gevonden.';
            }
        }
    }
}

?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Login</div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Wachtwoord</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/" class="btn btn-secondary">Terug</a>
                            <button class="btn btn-primary" type="submit">Login</button>
                        </div>
                    </form>
                    <a href="register.php" class="btn btn-link mt-3">Maak een account aan</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
