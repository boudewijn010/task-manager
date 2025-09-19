<?php
session_start();

// DB settings - keep in sync with index.php
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
    die('Database connectie mislukt: ' . $e->getMessage());
}

// If already logged in, redirect
if (!empty($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        // Try to find user in users table
        try {
            $stmt = $db->prepare('SELECT id, email, password FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $userRow = $stmt->fetch();
        } catch (Exception $e) {
            $userRow = false;
        }

        if ($userRow) {
            // Expect password to be hashed with password_hash
            if (password_verify($password, $userRow['password'])) {
                $_SESSION['user_id'] = $userRow['id'];
                $_SESSION['user_email'] = $userRow['email'];
                header('Location: /');
                exit;
            } else {
                $error = 'Ongeldig wachtwoord.';
            }
        } else {
            // Fallback: if no users table or user not found, allow root with empty password (development only)
            if ($email === 'root' && $password === '') {
                $_SESSION['user_id'] = 0;
                $_SESSION['user_email'] = 'root';
                header('Location: /');
                exit;
            }
            $error = 'Gebruiker niet gevonden.';
        }
    } else {
        $error = 'Vul email en wachtwoord in.';
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
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
