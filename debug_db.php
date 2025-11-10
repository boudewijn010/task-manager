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
    echo "Connected to DB: {$dbname}\n\n";

    // List tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);
    echo "Tables in database:\n";
    foreach ($tables as $t) { echo " - " . $t[0] . "\n"; }
    echo "\n";

    // Check users table existence and count
    $exists = false;
    $tableNames = array_map(function($r){ return strtolower($r[0]); }, $tables);
    foreach ($tableNames as $tn) { if ($tn === 'users' || $tn === 'gebruikers') { $exists = true; break; } }
    if ($exists) {
        $c = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch();
        echo "users table exists, rows: " . ($c['cnt'] ?? '0') . "\n";
        $r = $pdo->query("SELECT id, email FROM users LIMIT 5")->fetchAll();
        echo "Sample rows:\n";
        print_r($r);
    } else {
        echo "users table not found (case-insensitive check).\n";
    }

} catch (PDOException $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}
