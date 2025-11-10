<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'gemeente-app-db';
try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $row = $pdo->query("SELECT id, name, password FROM gebruikers LIMIT 1")->fetch();
    if ($row) {
        echo "Found gebruikers row:\n";
        echo "id: " . $row['id'] . "\n";
        echo "name: " . $row['name'] . "\n";
        echo "password present: " . (empty($row['password']) ? 'no' : 'yes') . "\n";
    } else {
        echo "No rows in gebruikers table.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
